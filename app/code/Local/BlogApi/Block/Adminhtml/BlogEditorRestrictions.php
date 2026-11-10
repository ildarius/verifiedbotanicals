<?php
declare(strict_types=1);

namespace Local\BlogApi\Block\Adminhtml;

use Magento\Backend\Block\AbstractBlock;

/**
 * Page Builder's HTML Code element hard-codes Insert Widget / Insert Variable buttons with no permission
 * check. On blog post pages, hide them for users whose role cannot open them.
 */
class BlogEditorRestrictions extends AbstractBlock
{
    protected function _toHtml(): string
    {
        $selectors = [];
        if (!$this->_authorization->isAllowed('Magento_Widget::widget_instance')) {
            $selectors[] = '.admin__control-wysiwig .buttons-set .action-add-widget';
        }
        if (!$this->_authorization->isAllowed('Magento_Variable::variable')) {
            $selectors[] = '.admin__control-wysiwig .buttons-set .add-variable';
        }

        return $selectors ? '<style>' . implode(',', $selectors) . '{display:none!important}</style>' : '';
    }
}
