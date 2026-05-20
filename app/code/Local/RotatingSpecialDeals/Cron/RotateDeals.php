<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Cron;

use Local\RotatingSpecialDeals\Service\RotationManager;
use Psr\Log\LoggerInterface;

class RotateDeals
{
    private RotationManager $rotationManager;

    private LoggerInterface $logger;

    public function __construct(
        RotationManager $rotationManager,
        LoggerInterface $logger
    ) {
        $this->rotationManager = $rotationManager;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->rotationManager->rotateIfDue(false);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Rotating special deals cron failed.',
                ['exception' => $exception]
            );
        }
    }
}
