<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface LookbookRepositoryInterface
{

    /**
     * Save Lookbook
     *
     * @param \Coduzion\Lookbook\Api\Data\LookbookInterface $lookbook
     * @return \Coduzion\Lookbook\Api\Data\LookbookInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Coduzion\Lookbook\Api\Data\LookbookInterface $lookbook
    );

    /**
     * Retrieve Lookbook
     *
     * @param string $lookbookId
     * @return \Coduzion\Lookbook\Api\Data\LookbookInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($lookbookId);

    /**
     * Retrieve Lookbook matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Coduzion\Lookbook\Api\Data\LookbookSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Lookbook
     *
     * @param \Coduzion\Lookbook\Api\Data\LookbookInterface $lookbook
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Coduzion\Lookbook\Api\Data\LookbookInterface $lookbook
    );

    /**
     * Delete Lookbook by ID
     *
     * @param string $lookbookId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($lookbookId);
}
