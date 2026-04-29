<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Lookbook\Edit;

use Coduzion\Lookbook\Model\Lookbook;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;

/**
 * Admin page left menu.
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    public const ADVANCED_TAB_GROUP_CODE = 'advanced';

    /**
     * @var Lookbook
     */
    private $lookbook;

    /**
     * Tabs constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Coduzion\Lookbook\Model\Lookbook $lookbook
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Session $authSession,
        Lookbook $lookbook,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->lookbook = $lookbook;
    }

    /**
     * Construct method
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('lookbook_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Lookbook Details'));
    }

    /**
     * Before to html
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'main',
            [
            'label' => __('General'),
            'title' => __('General'),
            'content' => $this->getLayout()->createBlock(
                \Coduzion\Lookbook\Block\Adminhtml\Lookbook\Edit\Tab\Main::class
            )->toHtml(),
            'active' => true,
                ]
        );

        if ($this->getRequest()->getParam('lookbook_id')) {
            $lookbookCollection = $this->lookbook->getCollection()
                ->addFieldToSelect('*')->addFieldToFilter(
                    'lookbook_id',
                    ['eq' => $this->getRequest()->getParam('lookbook_id')]
                );

            if (count($lookbookCollection) > 0) {
                $this->addTab(
                    'add_markers',
                    [
                    'label' => __('Add Markers'),
                    'title' => __('Add Markers'),
                             'class' => 'ajax',
                             'url' => $this->getUrl('lookbook/*/marker', ['_current' => true]),
                    'active' => true,
                        ]
                );
            }
        }
        return parent::_beforeToHtml();
    }
}
