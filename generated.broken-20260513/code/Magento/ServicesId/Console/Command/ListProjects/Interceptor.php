<?php
namespace Magento\ServicesId\Console\Command\ListProjects;

/**
 * Interceptor class for @see \Magento\ServicesId\Console\Command\ListProjects
 */
class Interceptor extends \Magento\ServicesId\Console\Command\ListProjects implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\ServicesId\Model\ServicesConfigInterface $servicesConfig, \Magento\ServicesId\Model\ServicesClientInterface $servicesClient, \Magento\Framework\App\Config\ScopeConfigInterface $config, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($servicesConfig, $servicesClient, $config, $logger);
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
