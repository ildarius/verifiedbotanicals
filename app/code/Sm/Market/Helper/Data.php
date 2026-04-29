<?php
/*------------------------------------------------------------------------
# SM Market - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Market\Helper;

use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    protected $_logo;
	protected $_storeManager;
	
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Theme\Block\Html\Header\Logo $logo
    )
    {
        $this->_storeManager = $storeManagerInterface;
        $this->_logo         = $logo;
        parent::__construct($context);
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    public function getUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getUrlStatic()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
    }

    public function getLocale()
    {
        return $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getThemeLayout($name)
    {
        return $this->scopeConfig->getValue(
            'market/theme_layout/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMobileThemeLayout($name)
    {
        return $this->scopeConfig->getValue(
            'market/mobile_theme_layout/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSmThemeInfo($name)
    {
        return $this->scopeConfig->getValue(
            'market/product_information/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isHomePage()
    {
        return $this->_logo->isHomePage();
    }
}