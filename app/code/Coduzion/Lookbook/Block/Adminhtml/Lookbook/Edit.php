<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Lookbook;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    public const URL_PATH_DUPLICATE = "lookbook/index/duplicate";

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Edit constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize look edit block.
     */
    protected function _construct()
    {
        $this->_objectId = 'lookbook_id';
        $this->_blockGroup = 'Coduzion_Lookbook';
        $this->_controller = 'adminhtml_lookbook';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Lookbook'));
        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ],
            ],
            -100
        );
        $duplicateUrl = $this->_urlBuilder->getUrl(
            static::URL_PATH_DUPLICATE,
            [
                'lookbook_id' => $this->getRequest()->getParam('lookbook_id'),
            ]
        );
        
        $this->buttonList->update('delete', 'label', __('Delete Lookbook'));
    }

    /**
     * Retrieve text for header element depending on loaded look.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('look_look')->getId()) {
            return __("Edit Lookbook '%1'", $this->escapeHtml(
                $this->_coreRegistry->registry('look_look')->getLookName()
            ));
        } else {
            return __('New Lookbook');
        }
    }

    /**
     * Check permission for passed action.
     *
     * @param string $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('lookbook/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}
