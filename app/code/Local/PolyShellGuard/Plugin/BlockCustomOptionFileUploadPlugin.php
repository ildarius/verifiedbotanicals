<?php

namespace Local\PolyShellGuard\Plugin;

use Magento\Catalog\Model\Webapi\Product\Option\Type\File\Processor;
use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * Rejects files sent as cart item custom option `file_info` through the web API.
 *
 * The store has no "File" type custom options, so this path has no legitimate use and is
 * the PolyShell upload vector. Storefront (non-API) file options use a different validator.
 */
class BlockCustomOptionFileUploadPlugin
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function beforeProcessFileContent(Processor $subject, ImageContentInterface $imageContent): array
    {
        $this->logger->warning('PolyShellGuard: blocked custom option file upload via API.', [
            'name' => (string)$imageContent->getName(),
            'type' => (string)$imageContent->getType(),
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        throw new InputException(new Phrase('File uploads for custom options are not supported.'));
    }
}
