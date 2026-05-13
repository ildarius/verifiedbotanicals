<?php
namespace Magento\PaymentServicesPaypal\Console\Command\CleanMerchantScopesCache;

/**
 * Interceptor class for @see \Magento\PaymentServicesPaypal\Console\Command\CleanMerchantScopesCache
 */
class Interceptor extends \Magento\PaymentServicesPaypal\Console\Command\CleanMerchantScopesCache implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\PaymentServicesBase\Model\MerchantCacheService $cacheService)
    {
        $this->___init();
        parent::__construct($cacheService);
    }

    /**
     * {@inheritdoc}
     */
    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'run');
        return $pluginInfo ? $this->___callPlugins('run', func_get_args(), $pluginInfo) : parent::run($input, $output);
    }
}
