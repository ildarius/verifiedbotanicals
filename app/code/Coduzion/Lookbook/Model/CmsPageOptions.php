<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Model;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

class CmsPageOptions
{
    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $pageCollectionFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory
     */
    public function __construct(
        PageCollectionFactory $pageCollectionFactory
    ) {
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Get options for CMS pages as an array
     *
     * @return array
     */
    public function getCmsPagesOptions()
    {
        $options = [];

        // Retrieve the CMS pages collection
        $cmsPagesCollection = $this->pageCollectionFactory->create();

        // Add CMS pages to the options array
        foreach ($cmsPagesCollection as $cmsPage) {
            $options[] = [
                'label' => $cmsPage->getTitle(),
                'value' => $cmsPage->getId(),
            ];
        }

        return $options;
    }
}
