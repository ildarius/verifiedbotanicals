<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model;

use Coduzion\Lookbook\Api\Data\LookbookInterface;
use Coduzion\Lookbook\Api\Data\LookbookInterfaceFactory;
use Coduzion\Lookbook\Api\Data\LookbookSearchResultsInterfaceFactory;
use Coduzion\Lookbook\Api\LookbookRepositoryInterface;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook as ResourceLookbook;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook\CollectionFactory as LookbookCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class LookbookRepository implements LookbookRepositoryInterface
{

    /**
     * @var Lookbook
     */
    protected $searchResultsFactory;

    /**
     * @var ResourceLookbook
     */
    protected $resource;

    /**
     * @var LookbookCollectionFactory
     */
    protected $lookbookCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var LookbookInterfaceFactory
     */
    protected $lookbookFactory;

    /**
     * Construct method
     *
     * @param ResourceLookbook $resource
     * @param LookbookInterfaceFactory $lookbookFactory
     * @param LookbookCollectionFactory $lookbookCollectionFactory
     * @param LookbookSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceLookbook $resource,
        LookbookInterfaceFactory $lookbookFactory,
        LookbookCollectionFactory $lookbookCollectionFactory,
        LookbookSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->lookbookFactory = $lookbookFactory;
        $this->lookbookCollectionFactory = $lookbookCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(LookbookInterface $lookbook)
    {
        try {
            $this->resource->save($lookbook);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the lookbook: %1',
                $exception->getMessage()
            ));
        }
        return $lookbook;
    }

    /**
     * @inheritDoc
     */
    public function get($lookbookId)
    {
        $lookbook = $this->lookbookFactory->create();
        $this->resource->load($lookbook, $lookbookId);
        if (!$lookbook->getId()) {
            throw new NoSuchEntityException(__('Lookbook with id "%1" does not exist.', $lookbookId));
        }
        return $lookbook;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->lookbookCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(LookbookInterface $lookbook)
    {
        try {
            $lookbookModel = $this->lookbookFactory->create();
            $this->resource->load($lookbookModel, $lookbook->getLookbookId());
            $this->resource->delete($lookbookModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Lookbook: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($lookbookId)
    {
        return $this->delete($this->get($lookbookId));
    }
}
