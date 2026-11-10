<?php
declare(strict_types=1);

namespace Local\BlogApi\Controller\Adminhtml\Product;

/**
 * Read-only lookup of the product already chosen in a "Link to Product" picker, for blog editors.
 * Same as catalog/product/getSelected, but gated on Magefan_Blog::post_save instead of Catalog > Products.
 */
class GetSelected extends \Magento\Catalog\Controller\Adminhtml\Product\GetSelected
{
    public const ADMIN_RESOURCE = 'Magefan_Blog::post_save';
}
