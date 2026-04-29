<?php
/*------------------------------------------------------------------------
# SM Categories - Version 3.2.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\DegreeView\Helper;

use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $productRepository ;
	protected $_storeManager ;
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->productRepository = $productRepository;
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
            'degreeview/general/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }


}