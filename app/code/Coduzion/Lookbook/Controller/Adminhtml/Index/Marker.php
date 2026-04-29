<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory ;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Controller\Result\RawFactory;

class Marker extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Coduzion_Lookbook::lookbook_save';
    
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LayoutFactory $layoutFactory
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LayoutFactory $layoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->layoutFactory = $layoutFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    /**
     * Execute Method
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock(\Coduzion\Lookbook\Block\Adminhtml\Lookbook\Edit\Tab\Markers::class)
            ->toHtml();
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents($output);
    }
}
