<?php
/**
 *
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;

class Chooser extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_CatalogRule::promo_catalog';

    /**
     * Prepare block for chooser
     *
     * @return void
     */
    public function execute()
    {
        $request = $this->getRequest();

        $block = false;
        if ($request->getParam('attribute') == 'lookbook_id') {
            $block = $this->_view->getLayout()->createBlock(
                \Coduzion\Lookbook\Block\Adminhtml\Widget\Chooser\Lookbook::class,
                'lookbook_widget_chooser_id',
                ['data' => ['js_form_object' => $request->getParam('form')]]
            );
        }

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
