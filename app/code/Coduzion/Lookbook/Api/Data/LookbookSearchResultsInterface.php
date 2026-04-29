<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Api\Data;

interface LookbookSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Lookbook list.
     *
     * @return \Coduzion\Lookbook\Api\Data\LookbookInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     *
     * @param \Coduzion\Lookbook\Api\Data\LookbookInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
