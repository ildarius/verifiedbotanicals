<?php
namespace Magento\Integration\Controller\Token\Access;

/**
 * Interceptor class for @see \Magento\Integration\Controller\Token\Access
 */
class Interceptor extends \Magento\Integration\Controller\Token\Access implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Oauth\OauthInterface $oauthService, \Magento\Integration\Api\OauthServiceInterface $intOauthService, \Magento\Integration\Api\IntegrationServiceInterface $integrationService, \Magento\Framework\Oauth\Helper\Request $helper)
    {
        $this->___init();
        parent::__construct($context, $oauthService, $intOauthService, $integrationService, $helper);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
