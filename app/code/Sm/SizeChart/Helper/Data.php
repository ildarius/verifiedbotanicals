<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product description block
 *
 * @author     Magentech.com
 */

namespace Sm\SizeChart\Helper;

use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_storeManager;
    /**
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->_storeManager = $storeManagerInterface;
        parent::__construct($context);
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getUrlStatic()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
    }

    /**
     * @return mixed
     */

    public function getLocale()
    {
        return $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getConfig($name)
    {
        return $this->scopeConfig->getValue(
            'sizechart/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */

    public function getSizeChartTitle()
    {
        return $this->scopeConfig->getValue(
            'sizechart/general/title_sizechart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}