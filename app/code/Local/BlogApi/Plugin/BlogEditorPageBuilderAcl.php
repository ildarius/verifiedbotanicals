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
 * The widget endpoints stay protected by their own ACL. Content types and editor buttons the role cannot use are
 * hidden from the Page Builder panel (they still render if a post already contains them).
 */
class BlogEditorPageBuilderAcl
{
    private const BLOG_ROUTE = 'blog';
    private const POST_SAVE_RESOURCE = 'Magefan_Blog::post_save';

    /** Content type => ACL resource its editor needs. */
    private const CONTENT_TYPE_RESOURCES = [
        'block' => 'Magento_Cms::block',
        'dynamic_block' => 'Magento_Cms::block',
        'products' => 'Magento_Widget::widget_instance',
    ];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    public function afterGetConfig(Config $subject, array $result): array
    {
        if ($this->request->getRouteName() !== self::BLOG_ROUTE
            || !$this->authorization->isAllowed(self::POST_SAVE_RESOURCE)
        ) {
            return $result;
        }

        if (isset($result['acl']['widget']) && $result['acl']['widget'] === false) {
            $result['acl']['widget'] = true;
        }

        foreach (self::CONTENT_TYPE_RESOURCES as $type => $resource) {
            if (isset($result['content_types'][$type]) && !$this->authorization->isAllowed($resource)) {
                $result['content_types'][$type]['is_system'] = false;
            }
        }

        // The inline editors of Text, Banner and Slide get their TinyMCE config from a cache shared by all
        // admin users, so the Insert Widget / Insert Variable plugins are removed here, per request.
        $denied = [];
        if (!$this->authorization->isAllowed('Magento_Widget::widget_instance')) {
            $denied['magentowidget'] = 'add_widgets';
        }
        if (!$this->authorization->isAllowed('Magento_Variable::variable')) {
            $denied['magentovariable'] = 'add_variables';
        }
        if ($denied && isset($result['content_types']) && is_array($result['content_types'])) {
            foreach ($result['content_types'] as $type => $contentType) {
                $adapter = $contentType['additional_data']['wysiwygConfig']['wysiwygConfigData']['adapter'] ?? null;
                if (!is_array($adapter)) {
                    continue;
                }
                foreach ($denied as $pluginName => $flag) {
                    $adapter[$flag] = false;
                }
                if (isset($adapter['plugins']) && is_array($adapter['plugins'])) {
                    $adapter['plugins'] = array_values(array_filter(
                        $adapter['plugins'],
                        static fn($plugin) => !isset($denied[$plugin['name'] ?? ''])
                    ));
                }
                $result['content_types'][$type]['additional_data']['wysiwygConfig']['wysiwygConfigData']['adapter']
                    = $adapter;
            }
        }

        return $result;
    }
}
