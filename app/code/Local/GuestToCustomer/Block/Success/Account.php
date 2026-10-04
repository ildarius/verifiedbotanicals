<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Block\Success;

use Local\GuestToCustomer\Model\NewAccountSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;

/**
 * Success-page account notice. Replaces the core "Create an Account" box (checkout.registration),
 * which is moved into this block and only rendered when the order is still a guest order.
 */
class Account extends Template
{
    public const MODE_NONE = 'none';
    public const MODE_FALLBACK = 'fallback';
    public const MODE_SET_PASSWORD = 'set_password';
    public const MODE_EXISTING = 'existing';

    private ?Order $order = null;

    private ?string $mode = null;

    public function __construct(
        Context $context,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly NewAccountSession $newAccountSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getMode(): string
    {
        if ($this->mode !== null) {
            return $this->mode;
        }

        $order = $this->getOrder();
        if (!$order->getId() || $this->customerSession->isLoggedIn()) {
            $this->mode = self::MODE_NONE;
        } elseif (!$order->getCustomerId()) {
            // Conversion disabled or failed: keep Magento's own registration prompt.
            $this->mode = self::MODE_FALLBACK;
        } elseif ($this->newAccountSession->getEligibleCustomerId($order) !== null) {
            $this->mode = self::MODE_SET_PASSWORD;
        } else {
            $this->mode = self::MODE_EXISTING;
        }

        return $this->mode;
    }

    public function getEmail(): string
    {
        return (string)$this->getOrder()->getCustomerEmail();
    }

    public function getSetPasswordUrl(): string
    {
        return $this->getUrl('guesttocustomer/account/setPassword');
    }

    public function getOrderViewUrl(): string
    {
        return $this->getUrl('sales/order/view', ['order_id' => (int)$this->getOrder()->getId()]);
    }

    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login');
    }

    public function getForgotPasswordUrl(): string
    {
        return $this->getUrl('customer/account/forgotpassword');
    }

    public function getMinimumPasswordLength(): int
    {
        return (int)$this->_scopeConfig->getValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    public function getRequiredCharacterClassesNumber(): int
    {
        return (int)$this->_scopeConfig->getValue(AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
    }

    private function getOrder(): Order
    {
        if ($this->order === null) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }

        return $this->order;
    }
}
