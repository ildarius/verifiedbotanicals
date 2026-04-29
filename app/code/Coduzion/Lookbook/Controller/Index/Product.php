<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Coduzion\Lookbook\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Helper\Image;

class Product extends Action
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * Constructor for the Product controller.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param \Magento\Catalog\Helper\Image $imageHelper
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        JsonFactory $jsonResultFactory,
        Image $imageHelper
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Controller action to retrieve product details by product ID.
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $result = $this->jsonResultFactory->create();

        if ($productId) {
            try {
                $product = $this->productRepository->getById($productId);
                $url = $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl();
                $productData = [
                    'product_id' => $product->getId(),
                    'product_name' => $product->getName(),
                    'image_url' => $url,
                    'price' => $product->getFinalPrice()
                ];

                return $result->setData($productData);
            } catch (\Exception $e) {
                return $result->setData(['error' => 'Product not found']);
            }
        } else {
            return $result->setData(['error' => 'Product ID not provided']);
        }
    }
}
