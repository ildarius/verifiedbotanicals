<?php

namespace Local\PolyShellGuard\Plugin;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\File\ImageContentProcessor;
use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * 2.4.9+: web API cart item custom option `file_info` goes through ImageContentProcessor
 * (via CustomOptionProcessor) instead of Webapi\Product\Option\Type\File\Processor, so
 * BlockCustomOptionFileUploadPlugin alone no longer covers it. The native path keeps the
 * client-supplied file name (e.g. "x.php.png"), which cPanel's AddHandler can execute.
 */
class BlockImageContentProcessorPlugin
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function beforeProcess(
        ImageContentProcessor $subject,
        ImageContentInterface $imageContent,
        Option $option
    ): array {
        $this->logger->warning('PolyShellGuard: blocked custom option file upload via API.', [
            'name' => (string)$imageContent->getName(),
            'type' => (string)$imageContent->getType(),
            'option_id' => (int)$option->getId(),
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        throw new InputException(new Phrase('File uploads for custom options are not supported.'));
    }
}
