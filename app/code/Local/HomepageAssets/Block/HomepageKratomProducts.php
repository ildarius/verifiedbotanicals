<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Block;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template\Context;

class HomepageKratomProducts extends AbstractProduct
{
    private const KRATOM_SKUS = ['GH', 'GM', 'GMD', 'RH', 'RMD', 'RB'];

    private ?Collection $productCollection = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CatalogConfig $catalogConfig,
        private readonly ResourceConnection $resourceConnection,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductCollection(): Collection
    {
        if ($this->productCollection !== null) {
            return $this->productCollection;
        }

        $limit = max(1, (int)($this->getData('limit') ?: 8));
        $connection = $this->resourceConnection->getConnection();
        $productIds = $connection->fetchCol(
            $connection->select()
                ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id'])
                ->where('sku IN (?)', self::KRATOM_SKUS)
                ->order('created_at DESC')
        );

        $collection = $this->collectionFactory->create();
        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setStoreId((int)$this->_storeManager->getStore()->getId())
            ->addIdFilter($productIds);
        $collection->getSelect()->order('created_at DESC');
        $collection->setPageSize($limit);
        $collection->setCurPage(1);

        $this->productCollection = $collection;
        return $this->productCollection;
    }
}
