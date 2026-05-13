<?php
namespace Magento\Translation\Controller\Ajax\Index;

/**
 * Interceptor class for @see \Magento\Translation\Controller\Ajax\Index
 */
class Interceptor extends \Magento\Translation\Controller\Ajax\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Translate\Inline\ParserInterface $inlineParser)
    {
        $this->___init();
        parent::__construct($context, $inlineParser);
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
