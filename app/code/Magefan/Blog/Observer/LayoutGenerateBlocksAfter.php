<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\Blog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magefan\Blog\Model\Config as BlogConfig;

class LayoutGenerateBlocksAfter implements ObserverInterface
{
    /**
     * @var \Magento\Framework\View\Page\Config
     */
    private $pageConfig;

    /**
     * @var BlogConfig
     */
    private $blogConfig;

    /**
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @param BlogConfig $scopeConfig
     */
    public function __construct(
        \Magento\Framework\View\Page\Config $pageConfig,
        BlogConfig $blogConfig
    ) {
        $this->pageConfig = $pageConfig;
        $this->blogConfig = $blogConfig;
    }

    /**
     * Add rel prev and rel next
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $availableActions = [
            'blog_archive_view',
            'blog_author_view',
            'blog_category_view',
            'blog_index_index',
            'blog_tag_view'
        ];
        $fan = $observer->getEvent()->getFullActionName();
        if (!in_array($fan, $availableActions)) {
            return;
        }

        if ('blog_index_index' == $fan) {
            $displayMode = $this->blogConfig->getConfig(
                BlogConfig::XML_PATH_HOMEPAGE_DISPLAY_MODE
            );

            if (2 == $displayMode) {
                return;
            }
        }

        $productListBlock = $observer->getEvent()->getLayout()->getBlock('blog.posts.list');
        if (!$productListBlock) {
            return;
        }

        // Local patch (2026-11-13): page_cache/block/render replays the original full action name but
        // has no current category/tag/author registered, so getPostCollection() fatals (HTTP 500).
        foreach (['getCategory', 'getTag', 'getAuthor'] as $getter) {
            if (method_exists($productListBlock, $getter) && !$productListBlock->$getter()) {
                return;
            }
        }

        $toolbar = $productListBlock->getToolbarBlock();
        $toolbar->setCollection($productListBlock->getPostCollection());

        $pagerBlock = $toolbar->getPagerBlock();
        if (!($pagerBlock instanceof \Magento\Framework\DataObject)) {
            return;
        }

        if (1 < $pagerBlock->getCurrentPage()) {
            $this->pageConfig->addRemotePageAsset(
                $pagerBlock->getPageUrl(
                    $pagerBlock->getCollection()->getCurPage(-1)
                ),
                'link_rel',
                ['attributes' => ['rel' => 'prev']]
            );
        }
        if ($pagerBlock->getCurrentPage() < $pagerBlock->getLastPageNum()) {
            $this->pageConfig->addRemotePageAsset(
                $pagerBlock->getPageUrl(
                    $pagerBlock->getCollection()->getCurPage(+1)
                ),
                'link_rel',
                ['attributes' => ['rel' => 'next']]
            );
        }
    }
}
