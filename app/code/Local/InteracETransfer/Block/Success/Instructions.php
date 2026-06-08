<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Block\Success;

use Local\InteracETransfer\Model\Method\InteracETransfer;
use Local\InteracETransfer\Service\InstructionDataBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderInterface;

class Instructions extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Session $checkoutSession,
        private readonly InstructionDataBuilder $instructionDataBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function canShowInstructions(): bool
    {
        $order = $this->getOrder();

        return $order !== null
            && $order->getPayment() !== null
            && $order->getPayment()->getMethod() === InteracETransfer::PAYMENT_METHOD_CODE;
    }

    public function getOrder(): ?OrderInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order && $order->getEntityId() ? $order : null;
    }

    /**
     * @return array<string,string|int>
     */
    public function getInstructionData(): array
    {
        return $this->instructionDataBuilder->build($this->getOrder());
    }
}
