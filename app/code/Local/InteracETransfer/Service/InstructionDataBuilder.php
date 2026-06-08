<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Service;

use Local\InteracETransfer\Model\Config;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;

class InstructionDataBuilder
{
    public function __construct(
        private readonly Config $config,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @return array<string,string|int>
     */
    public function build(?OrderInterface $order = null): array
    {
        $storeId = $order ? (int)$order->getStoreId() : null;
        $payment = $order ? $order->getPayment() : null;
        $paymentData = $payment ? $payment->getAdditionalInformation() : [];

        $recipientName = $this->readAdditionalString(
            $paymentData,
            'interac_recipient_name',
            $this->config->getRecipientName($storeId)
        );
        $recipientEmail = $this->readAdditionalString(
            $paymentData,
            'interac_recipient_email',
            $this->config->getRecipientEmail($storeId)
        );
        $paymentWindowHours = $this->readAdditionalInt(
            $paymentData,
            'interac_payment_window_hours',
            $this->config->getPaymentWindowHours($storeId)
        );
        $orderNumber = $this->readAdditionalString(
            $paymentData,
            'interac_reference',
            $order ? (string)$order->getIncrementId() : ''
        );
        $deadlineAt = $this->readAdditionalString(
            $paymentData,
            'interac_deadline_at',
            $this->buildDeadlineAtValue($order, $paymentWindowHours)
        );
        $deadlineAt = $this->formatStoredDeadline($deadlineAt, $order, $paymentWindowHours);

        return [
            'heading' => 'Your order has been placed. Payment is still required.',
            'intro' => 'We have received your order and placed it on hold pending your Interac e-Transfer. We will begin processing once payment has been received and matched to your order.',
            'short_notice' => $this->config->getShortNotice($storeId),
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'payment_window_hours' => $paymentWindowHours,
            'order_number' => $orderNumber,
            'order_date' => $this->formatOrderDate($order),
            'order_total' => $this->formatOrderTotal($order),
            'deadline_at' => $deadlineAt,
            'reference_instruction' => $orderNumber !== ''
                ? sprintf('Include order #%s in the Interac message field so we can match payment quickly.', $orderNumber)
                : 'Include your Magento order number in the Interac message field so we can match payment quickly.',
            'recipient_instruction' => sprintf(
                'Send the transfer to %s at %s.',
                $recipientName !== '' ? $recipientName : 'Verified Botanicals',
                $recipientEmail !== '' ? $recipientEmail : 'the configured recipient email'
            ),
            'privacy_instruction' => 'For privacy and processing reasons, do not add extra product-related wording in the recipient name or transfer message beyond your order reference.',
            'followup_instruction' => sprintf(
                'Once payment is confirmed, your order will move into processing. Orders that remain unpaid beyond %d hours may be canceled automatically.',
                $paymentWindowHours
            ),
        ];
    }

    private function buildDeadlineAtValue(?OrderInterface $order, int $paymentWindowHours): string
    {
        if ($order && $order->getCreatedAt()) {
            $createdAt = new \DateTimeImmutable((string)$order->getCreatedAt(), new \DateTimeZone('UTC'));
        } else {
            $createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }

        $deadline = $createdAt->modify(sprintf('+%d hours', $paymentWindowHours));
        $timezone = new \DateTimeZone($this->timezone->getConfigTimezone('store', $order ? (int)$order->getStoreId() : null));

        return $deadline->setTimezone($timezone)->format('F j, Y g:i A');
    }

    private function formatStoredDeadline(string $value, ?OrderInterface $order, int $paymentWindowHours): string
    {
        if ($value === '') {
            return $this->buildDeadlineAtValue($order, $paymentWindowHours);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) !== 1) {
            return $value;
        }

        $timezone = new \DateTimeZone($this->timezone->getConfigTimezone('store', $order ? (int)$order->getStoreId() : null));
        $dateTime = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));

        return $dateTime->setTimezone($timezone)->format('F j, Y g:i A');
    }

    private function formatOrderDate(?OrderInterface $order): string
    {
        if (!$order || !$order->getCreatedAt()) {
            return '';
        }

        $createdAt = new \DateTimeImmutable((string)$order->getCreatedAt(), new \DateTimeZone('UTC'));
        $timezone = new \DateTimeZone($this->timezone->getConfigTimezone('store', (int)$order->getStoreId()));

        return $createdAt->setTimezone($timezone)->format('F j, Y g:i A');
    }

    private function formatOrderTotal(?OrderInterface $order): string
    {
        if (!$order) {
            return '';
        }

        return (string)$this->priceCurrency->format(
            (float)$order->getGrandTotal(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $order->getStore()
        );
    }

    /**
     * @param mixed[] $paymentData
     */
    private function readAdditionalString(array $paymentData, string $key, string $fallback): string
    {
        $value = $paymentData[$key] ?? null;
        if (!is_scalar($value)) {
            return $fallback;
        }

        $stringValue = trim((string)$value);

        return $stringValue !== '' ? $stringValue : $fallback;
    }

    /**
     * @param mixed[] $paymentData
     */
    private function readAdditionalInt(array $paymentData, string $key, int $fallback): int
    {
        $value = $paymentData[$key] ?? null;
        if (!is_scalar($value)) {
            return $fallback;
        }

        return max(1, (int)$value);
    }
}
