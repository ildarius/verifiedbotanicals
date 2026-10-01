<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Console\Command;

use Local\GuestToCustomer\Service\GuestOrderConverter;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfill: converts orders that were placed as guest before the observer existed.
 * Ignores the admin "enabled" flag on purpose - running it is the explicit opt-in.
 */
class ConvertGuestOrdersCommand extends Command
{
    private const OPTION_ORDER = 'order';
    private const OPTION_SKIP_CANCELED = 'skip-canceled';
    private const OPTION_NO_LINK = 'no-link';
    private const OPTION_DRY_RUN = 'dry-run';

    public function __construct(
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly GuestOrderConverter $converter,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('local:guest-to-customer:convert')
            ->setDescription('Create customer accounts for existing guest orders and attach the orders to them')
            ->addOption(
                self::OPTION_ORDER,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only this order increment ID (repeatable), e.g. --order=111000000007'
            )
            ->addOption(self::OPTION_SKIP_CANCELED, null, InputOption::VALUE_NONE, 'Leave canceled orders as guest orders')
            ->addOption(self::OPTION_NO_LINK, null, InputOption::VALUE_NONE, 'Do not attach orders to accounts that already exist')
            ->addOption(self::OPTION_DRY_RUN, null, InputOption::VALUE_NONE, 'Show what would happen without changing anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Frontend area so the welcome email renders with the storefront templates.
            $this->appState->setAreaCode(Area::AREA_FRONTEND);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            // Area code may already be initialized by Magento.
        }

        $dryRun = (bool)$input->getOption(self::OPTION_DRY_RUN);
        $linkExisting = !$input->getOption(self::OPTION_NO_LINK);

        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', ['null' => true])
            ->setOrder('entity_id', 'ASC');
        if ($incrementIds = $input->getOption(self::OPTION_ORDER)) {
            $collection->addFieldToFilter('increment_id', ['in' => $incrementIds]);
        }
        if ($input->getOption(self::OPTION_SKIP_CANCELED)) {
            $collection->addFieldToFilter('state', ['neq' => Order::STATE_CANCELED]);
        }

        $failures = 0;
        /** @var Order $order */
        foreach ($collection as $order) {
            $label = sprintf('#%s %s (%s)', $order->getIncrementId(), $order->getCustomerEmail(), $order->getStatus());

            if (!$this->converter->canConvert($order)) {
                $output->writeln(sprintf('<comment>%s: skipped, no email</comment>', $label));
                continue;
            }

            if ($dryRun) {
                $existing = $this->converter->findExistingCustomer($order);
                $action = $existing === null
                    ? 'would create a new account and send the welcome email'
                    : ($linkExisting
                        ? sprintf('would attach to existing customer %d', $existing->getId())
                        : sprintf('skipped, customer %d already exists', $existing->getId()));
                $output->writeln(sprintf('%s: %s', $label, $action));
                continue;
            }

            try {
                $result = $this->converter->convert($order, $linkExisting);
            } catch (\Throwable $exception) {
                $failures++;
                $output->writeln(sprintf('<error>%s: %s</error>', $label, $exception->getMessage()));
                continue;
            }

            $message = match ($result['result']) {
                GuestOrderConverter::RESULT_CREATED => sprintf('created customer %d', $result['customer_id']),
                GuestOrderConverter::RESULT_LINKED => sprintf('attached to existing customer %d', $result['customer_id']),
                default => 'skipped',
            };
            $output->writeln(sprintf('<info>%s: %s</info>', $label, $message));
        }

        return $failures ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }
}
