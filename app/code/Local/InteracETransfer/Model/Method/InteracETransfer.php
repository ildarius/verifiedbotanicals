<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Model\Method;

use Local\InteracETransfer\Block\Info\InteracETransfer as InfoBlock;
use Local\InteracETransfer\Model\Config;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;

class InteracETransfer extends AbstractMethod
{
    public const PAYMENT_METHOD_CODE = 'interac_etransfer';

    protected $_code = self::PAYMENT_METHOD_CODE;

    protected $_infoBlockType = InfoBlock::class;

    protected $_isOffline = true;

    protected $_isInitializeNeeded = true;

    protected $_canOrder = true;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        private readonly Config $config,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function initialize($paymentAction, $stateObject): static
    {
        $storeId = $this->getInfoInstance() && $this->getInfoInstance()->getOrder()
            ? (int)$this->getInfoInstance()->getOrder()->getStoreId()
            : null;

        $stateObject->setData('state', Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('status', $this->config->getOrderStatus($storeId));
        $stateObject->setData('is_notified', false);

        $this->persistInstructionSnapshot();

        return $this;
    }

    private function persistInstructionSnapshot(): void
    {
        $info = $this->getInfoInstance();
        if (!$info) {
            return;
        }

        $order = $info->getOrder();
        $storeId = $order ? (int)$order->getStoreId() : null;
        $paymentWindowHours = $this->config->getPaymentWindowHours($storeId);
        $createdAt = $order && $order->getCreatedAt()
            ? new \DateTimeImmutable((string)$order->getCreatedAt(), new \DateTimeZone('UTC'))
            : new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $info->setAdditionalInformation('interac_recipient_name', $this->config->getRecipientName($storeId));
        $info->setAdditionalInformation('interac_recipient_email', $this->config->getRecipientEmail($storeId));
        $info->setAdditionalInformation('interac_payment_window_hours', $paymentWindowHours);
        $info->setAdditionalInformation('interac_reference', $order ? (string)$order->getIncrementId() : '');
        $info->setAdditionalInformation(
            'interac_deadline_at',
            $createdAt->modify(sprintf('+%d hours', $paymentWindowHours))->format('Y-m-d H:i:s')
        );
    }
}
