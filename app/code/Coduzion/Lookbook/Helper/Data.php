<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Helper;

use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storemanager;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $imageFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data as PriceHelper
     */
    protected $priceHelper;

    /**
     * Constructor. Initializes the class with necessary dependencies.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Image $imageHelper
     * @param StoreManagerInterface $storemanager
     * @param Session $customerSession
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Image\AdapterFactory $imageFactory
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Image $imageHelper,
        StoreManagerInterface $storemanager,
        Session $customerSession,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        PriceHelper $priceHelper,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->storemanager = $storemanager;
        $this->customerSession = $customerSession;
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->imageFactory = $imageFactory;
        $this->priceHelper = $priceHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve product information by product ID.
     *
     * @param int $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProductInfoFromId($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * Retrieve the URL of a product's thumbnail image by product ID.
     *
     * @param int $productId
     * @return string
     */
    public function getProductImg($productId)
    {
        $product = $this->productRepository->getById($productId);
        $url = $this->imageHelper->init($product, 'product_base_image')->getUrl();
        return $url;
    }

    /**
     * Retrieve the URL of a product's thumbnail image by product ID.
     *
     * @param float $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Get the customer group ID of the currently logged-in customer.
     *
     * @return int The customer group ID.
     */
    public function getGroupId()
    {
        return $this->customerSession->getCustomer()->getGroupId();
    }

    /**
     * Get the store ID of the currently active store.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storemanager->getStore()->getId();
    }

     /**
      * Resize and save an image to the specified width and height.
      *
      * @param string $imageName
      * @param int $width
      * @param int $height
      * @return string|false
      */
    public function getResizeImage($imageName, $width, $height)
    {
    /* Real path of image from directory */
        $realPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imageName);
        if (!$this->directory->isFile($realPath) || !$this->directory->isExist($realPath)) {
            return false;
        }
    
        $targetDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
        ->getAbsolutePath('resized/'.$width.'x'.$height);
        $pathTargetDir = $this->directory->getRelativePath($targetDir);
    
    /* If Directory not available, create it */
        if (!$this->directory->isExist($pathTargetDir)) {
            $this->directory->create($pathTargetDir);
        }
    
        if (!$this->directory->isExist($pathTargetDir)) {
            return false;
        }
    
        // @codingStandardsIgnoreStart
        $image = $this->imageFactory->create();
        $image->open($realPath);
        $image->keepAspectRatio(true);
        $image->resize($width, $height);
        $dest = $targetDir . '/' . pathinfo($realPath, PATHINFO_BASENAME);
        $image->save($dest);
    
        if ($this->directory->isFile($this->directory->getRelativePath($dest))) {
            // Return the correct resized image URL
            return $this->storemanager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'resized/' . $width . 'x' . $height . '/' . pathinfo($realPath, PATHINFO_BASENAME);
        }
        // @codingStandardsIgnoreEnd
        return false;
    }
}
