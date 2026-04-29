<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model;

use Magento\Store\Model\StoreManagerInterface;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\UrlInterface;
use Coduzion\Lookbook\Model\ImageUploader;

class DataProvider extends AbstractDataProvider
{

    /**
     * @var $loadedData
     */
    protected $loadedData;

    /**
     * @var $collection
     */
    protected $collection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return mixed
     */
    public function getData()
    {
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $model->getData();
            if ($model->getImageField()) {
                $m['lookbook_image'][0]['name'] = $model->getImageField();
                $m['lookbook_image'][0]['url'] = $this->getMediaUrl($model->getLookbookImage());
                $fullData = $this->loadedData;
                // @codingStandardsIgnoreStart
                $this->loadedData[$model->getId()] = array_merge($fullData[$model->getId()], $m);
                // @codingStandardsIgnoreEnd
            }
        }
        return $this->loadedData;
    }

    /**
     * Get media url
     *
     * @param string $path
     * @return string
     */
    public function getMediaUrl($path = '')
    {
        $dir = ImageUploader::BASE_PATH;
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $dir . $path;
        return $mediaUrl;
    }
}
