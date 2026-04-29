<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\Page;

abstract class Index extends \Magento\Backend\App\Action
{

    public const ADMIN_RESOURCE = 'Coduzion_Lookbook::top_level';

    /**
     * @var Registry
     */
    protected $_coreRegistry;
    
    /**
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param Page $resultPage
     * @return Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Coduzion'), __('Coduzion'))
            ->addBreadcrumb(__('Lookbook'), __('Lookbook'));
        return $resultPage;
    }
}
