<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Controller\Adminhtml\AssignProducts;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Coduzion\Lookbook\Model\LookbookFactory;

class Index extends Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Coduzion\Lookbook\Model\LookbookFactory
     */
    protected $lookbookFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param LookbookFactory $lookbookFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        LookbookFactory $lookbookFactory
    ) {

        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->lookbookFactory = $lookbookFactory;
        return parent::__construct($context);
    }

    /**
     * Execute the controller action to render the Lookbook assignment page.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();

        $content = $resultPage->getLayout()
                ->createBlock(\Coduzion\Lookbook\Block\Adminhtml\Lookbook\AssignProducts::class)
                ->setTemplate('Coduzion_Lookbook::catalog/category/edit/assign_products.phtml')
                ->toHtml();

        $result = $this->resultJsonFactory->create();
        $result->setData(['content' => $content]);
        return $result;
    }
}
