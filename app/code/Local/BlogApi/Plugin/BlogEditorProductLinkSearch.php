<?php
declare(strict_types=1);

namespace Local\BlogApi\Plugin;

use Magento\Catalog\Ui\Component\UrlInput\Product;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;

/**
 * Page Builder's "Link to Product" picker searches through catalog/product/search, which needs the full
 * Catalog > Products permission (that also allows editing products). For blog editors without it,
 * point the picker at read-only copies of those endpoints that need only Magefan_Blog::post_save.
 */
class BlogEditorProductLinkSearch
{
    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function afterGetConfig(Product $subject, array $result): array
    {
        if (!$this->authorization->isAllowed('Magento_Catalog::products')
            && $this->authorization->isAllowed('Magefan_Blog::post_save')
        ) {
            $result['searchUrl'] = $this->urlBuilder->getUrl('blogeditor/product/search');
            $result['validationUrl'] = $this->urlBuilder->getUrl('blogeditor/product/getSelected');
        }

        return $result;
    }
}
