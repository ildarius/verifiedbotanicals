<?php
declare(strict_types=1);

namespace Local\BlogApi\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\PageBuilder\Model\Stage\Config;

/**
 * Page Builder refuses to open ("Permission Error: Sorry, you need permissions to view this content")
 * unless the admin user has Magento_Widget::widget_instance. Granting that to the Blog Editor role would
 * also let blog editors create storefront widgets, so instead this lifts only the client-side check, and
 * only on Magefan blog admin pages for users who may save posts.
 *
 * The widget endpoints stay protected by their own ACL, so "Insert Widget" inside the editor is still
 * denied for these users.
 */
class BlogEditorPageBuilderAcl
{
    private const BLOG_ROUTE = 'blog';
    private const POST_SAVE_RESOURCE = 'Magefan_Blog::post_save';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    public function afterGetConfig(Config $subject, array $result): array
    {
        if (isset($result['acl']['widget'])
            && $result['acl']['widget'] === false
            && $this->request->getRouteName() === self::BLOG_ROUTE
            && $this->authorization->isAllowed(self::POST_SAVE_RESOURCE)
        ) {
            $result['acl']['widget'] = true;
        }

        return $result;
    }
}
