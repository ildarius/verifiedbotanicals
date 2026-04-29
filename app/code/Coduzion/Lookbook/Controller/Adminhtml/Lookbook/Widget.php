<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Lookbook;

use Magento\Backend\App\Action;

/**
 * Class Widget
 */
abstract class Widget extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Widget::widget_instance';
}
