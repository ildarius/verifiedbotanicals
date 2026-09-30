<?php

namespace Local\PolyShellGuard\Plugin;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Api\ImageProcessor;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * Only lets base64 image uploads through when the file name is a plain image name and the
 * payload carries no embedded PHP (polyglot GIF/PNG files are how PolyShell payloads pass
 * Magento's getimagesize() check).
 */
class RestrictImageContentPlugin
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Refused as *any* dot-segment: cPanel's AddHandler also executes names like "x.php.png".
    private const FORBIDDEN_SEGMENT = '/^(ph(p\d*|t|tml?|ar|ps)|inc|module|s?html?|cgi|pl|py|jsp|asp|sh|htaccess)$/i';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function beforeProcessImageContent(ImageProcessor $subject, $entityType, $imageContent): array
    {
        if ($imageContent instanceof ImageContentInterface) {
            $reason = $this->getRejectionReason($imageContent);
            if ($reason !== null) {
                $this->logger->warning('PolyShellGuard: blocked image upload via API.', [
                    'reason' => $reason,
                    'entity_type' => (string)$entityType,
                    'name' => (string)$imageContent->getName(),
                    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                throw new InputException(
                    new Phrase('The image content is invalid. Verify the content and try again.')
                );
            }
        }

        return [$entityType, $imageContent];
    }

    /**
     * 2.4.9+ ImageContentUploaderInterface: moveFromTmpDir() keeps the client-supplied file name,
     * so apply the same checks before anything is written. Not called on 2.4.7 (method absent).
     */
    public function beforeSaveToTmpDir(
        ImageProcessor $subject,
        ImageContentInterface $imageContent,
        bool $validate = true
    ): array {
        $reason = $this->getRejectionReason($imageContent);
        if ($reason !== null) {
            $this->logger->warning('PolyShellGuard: blocked image upload via API.', [
                'reason' => $reason,
                'entity_type' => 'tmp_upload',
                'name' => (string)$imageContent->getName(),
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            throw new InputException(new Phrase('The image content is invalid. Verify the content and try again.'));
        }

        return [$imageContent, $validate];
    }

    private function getRejectionReason(ImageContentInterface $imageContent): ?string
    {
        $segments = explode('.', (string)$imageContent->getName());
        array_shift($segments);

        // No extension is fine: Magento appends one derived from the validated MIME type.
        if ($segments !== [] && !in_array(strtolower((string)end($segments)), self::ALLOWED_EXTENSIONS, true)) {
            return 'extension';
        }

        foreach ($segments as $segment) {
            if (preg_match(self::FORBIDDEN_SEGMENT, $segment)) {
                return 'segment';
            }
        }

        $data = base64_decode((string)$imageContent->getBase64EncodedData(), true);
        // Only "<?php": a 3-byte "<?=" occurs by chance in large binary images.
        if (is_string($data) && stripos($data, '<?php') !== false) {
            return 'php_payload';
        }

        return null;
    }
}
