<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Coduzion\Lookbook\Block\Adminhtml\Widget\Chooser\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Context;

class Image extends AbstractRenderer
{
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storemanager
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storemanager,
        array $data = []
    ) {
        $this->_storeManager = $storemanager;
        parent::__construct($context, $data);
        $this->_authorization = $context->getAuthorization();
    }

    /**
     * Renders grid column
     *
     * @param object $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $mediaDirectory = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        $imageUrl = $mediaDirectory.$this->_getValue($row);
        return '<img src="'.$imageUrl.'" width="50"/>';
    }
}
