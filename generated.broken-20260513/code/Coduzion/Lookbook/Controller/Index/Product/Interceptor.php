<?php
namespace Coduzion\Lookbook\Controller\Index\Product;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Index\Product
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Index\Product implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory, \Magento\Catalog\Helper\Image $imageHelper)
    {
        $this->___init();
        parent::__construct($context, $productRepository, $jsonResultFactory, $imageHelper);
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
