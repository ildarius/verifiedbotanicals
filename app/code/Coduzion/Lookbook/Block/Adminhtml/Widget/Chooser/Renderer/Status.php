<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Widget\Chooser\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class Status extends AbstractRenderer
{

    /**
     * Renders grid column
     *
     * @param Object $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->_getValue($row) ? 'Enabled' : 'Disabled';
    }
}
