<?php
declare(strict_types=1);

namespace Local\BlogApi\Controller\Adminhtml\Product;

/**
 * Read-only product search for Page Builder's "Link to Product" picker, for blog editors.
 * Same as catalog/product/search, but gated on Magefan_Blog::post_save instead of Catalog > Products.
 */
class Search extends \Magento\Catalog\Controller\Adminhtml\Product\Search
{
    public const ADMIN_RESOURCE = 'Magefan_Blog::post_save';
}
