<?php
/*------------------------------------------------------------------------
# SM Themecore
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Themecore\Helper;

use Magento\Store\Model\StoreManagerInterface;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $imageHelper ;
	protected $productRepository ; 
    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    )
    {
        $this->imageHelper       = $imageHelper;
        $this->productRepository = $productRepository;
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
}