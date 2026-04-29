<?php
/*------------------------------------------------------------------------
# SM Categories - Version 3.2.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\BundleImage\Helper;

use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $imageHelper ;
	protected $productRepository ; 
	protected $moduleManager ;  
	protected $_storeManager ;   
    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->imageHelper       = $imageHelper;
        $this->productRepository = $productRepository;
        $this->moduleManager     = $moduleManager;
        $this->_storeManager     = $storeManagerInterface;
        parent::__construct($context);
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getGeneral($name, $storeCode = null)
    {
        return $this->scopeConfig->getValue(
            'bundleimage/general/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    public function getQuickCartConfig($name, $storeCode = null)
    {
        return $this->scopeConfig->getValue(
            'cartquickpro/general/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    public function getItemImage($productId, $imageSize)
    {
        try {
            $_product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return 'product not found';
        }
        $image_url = $this->imageHelper->init($_product, $imageSize)->getUrl();
        return $image_url;
    }

    /**
     * Check Quickview Button
     */

    function checkQuickView()
    {
        $showQuickviewConfig = $this->getGeneral('show_quickview');
        if ($this->moduleManager->isOutputEnabled('Sm_CartQuickPro') && $showQuickviewConfig) {
            $enableConfigModuleQuickCart = $this->getQuickCartConfig('isenabled');
            $enableConfigQuickView       = $this->getQuickCartConfig('select_type');

            if ($enableConfigModuleQuickCart && ($enableConfigQuickView == 'both' || $enableConfigQuickView == 'quickview')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}