<?php
declare(strict_types=1);

namespace Local\BlogApi\Plugin;

use Magento\Cms\Block\Adminhtml\Wysiwyg\Images\Content;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Magento_Cms::media_gallery covers browsing, uploading AND deleting any file or folder under
 * pub/media/wysiwyg. Deleting now also needs Local_BlogApi::media_delete, so restricted roles (Blog
 * Editor) can browse and upload images but cannot delete images used elsewhere on the site.
 * Administrators have Magento_Backend::all and are unaffected.
 */
class MediaDeleteGuard
{
    private const DELETE_RESOURCE = 'Local_BlogApi::media_delete';

    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly JsonFactory $jsonFactory
    ) {
    }

    /** Hide the Delete Folder / Delete Selected buttons in the media browser. */
    public function beforeGetButtonsHtml(Content $subject, $region = null): array
    {
        if (!$this->authorization->isAllowed(self::DELETE_RESOURCE)) {
            $subject->removeButton('delete_folder');
            $subject->removeButton('delete_files');
        }

        return [$region];
    }

    /** Refuse the delete requests themselves (DeleteFiles and DeleteFolder controllers). */
    public function aroundExecute($subject, callable $proceed): ResultInterface
    {
        if (!$this->authorization->isAllowed(self::DELETE_RESOURCE)) {
            return $this->jsonFactory->create()->setData([
                'error' => true,
                'message' => (string) __('You do not have permission to delete media files or folders.'),
            ]);
        }

        return $proceed();
    }
}
