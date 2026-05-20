<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Console\Command;

use Local\RotatingSpecialDeals\Service\RotationManager;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RotateDealsCommand extends Command
{
    private const OPTION_FORCE = 'force';

    private RotationManager $rotationManager;

    private State $appState;

    public function __construct(
        RotationManager $rotationManager,
        State $appState
    ) {
        $this->rotationManager = $rotationManager;
        $this->appState = $appState;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('local:rotating-special-deals:rotate')
            ->setDescription('Rotate the homepage special deals cycle')
            ->addOption(
                self::OPTION_FORCE,
                null,
                InputOption::VALUE_NONE,
                'Rotate immediately even if the current cycle is still active'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            // Area code may already be initialized by Magento.
        }

        try {
            $result = $this->rotationManager->rotateIfDue((bool)$input->getOption(self::OPTION_FORCE));
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        if (!empty($result['skipped'])) {
            $output->writeln(
                sprintf(
                    '<comment>%s</comment>',
                    (string)($result['message'] ?? 'An active cycle is already in place.')
                )
            );

            return Cli::RETURN_SUCCESS;
        }

        $output->writeln(
            sprintf(
                '<info>Cycle %d created: %s</info>',
                (int)$result['cycle_id'],
                implode(', ', $result['selected_skus'] ?? [])
            )
        );
        $output->writeln(
            sprintf('<info>Cycle ends at %s</info>', (string)$result['ends_at'])
        );

        return Cli::RETURN_SUCCESS;
    }
}
