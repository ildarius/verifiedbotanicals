<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Lookbook\Edit\Tab;

use Coduzion\Lookbook\Model\Lookbook;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Store\Model\System\Store;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Coduzion\Lookbook\Model\CmsPageOptions;
use Magento\Catalog\Block\Adminhtml\Category\Tree as CategoryTree;

/**
 * Adminhtml lookbook edit form.
 */
class Main extends Generic implements TabInterface
{
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var Config
     */
    protected $_wysiwygConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * @var Lookbook
     */
    private $lookbook;

     /**
      * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
      */
    protected $customerGroupCollectionFactory;

    /**
     * @var \Coduzion\Lookbook\Model\CmsPageOptions
     */
    protected $cmsPageOptions;

     /**
      * @var CategoryTree
      */
    protected $categoryTree;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param Lookbook $lookbook
     * @param Config $wysiwygConfig
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     * @param CmsPageOptions $cmsPageOptions
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        Lookbook $lookbook,
        Config $wysiwygConfig,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        CmsPageOptions $cmsPageOptions,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_systemStore = $systemStore;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->lookbook = $lookbook;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->cmsPageOptions = $cmsPageOptions;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('look_form');
        $this->setTitle(__('Look Information'));
    }

    /**
     * Prepare form.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $lookbookId = $this->getRequest()->getParam('lookbook_id', false);
        if ($lookbookId) {
            $model = $this->lookbook->load($lookbookId);
        } else {
            $model = $this->_coreRegistry->registry('look_look');
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setFormKey($this->getFormKey());

        $form->setHtmlIdPrefix('lookbook_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information')]
        );

        if (!empty($lookbookId) && $model->getLookbookId()) {
            $fieldset->addField('lookbook_id', 'hidden', ['name' => 'lookbook_id']);
        }

        $fieldset->addField(
            'title',
            'text',
            ['name' => 'title', 'label' => __('Lookbook Title'), 'title' => __('Lookbook Title'), 'required' => true]
        );

        if (!empty($lookbookId) && $model->getId()) {
            $image = $model->getLookbookImage();
            $isImageSet = (isset($image) && $image != null) ? true : false;
            $mediaDirectory = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            $imageUrl = $mediaDirectory. 'lookbook/' . $image;
        } else {
            $isImageSet = false;
        }

        $lookbookImage = $fieldset->addField(
            'lookbook_image',
            'image',
            [
                'name' => 'lookbook_image',
                'label' => __('Lookbook Image'),
                'title' => __('Lookbook Image'),
                'required' => true,
                'class' => '',
            ]
        );
        
        // @codingStandardsIgnoreStart
        if ($isImageSet == 0) {
            $lookbookImage->setAfterElementHtml(
                '<script type="text/javascript">$("lookbook_lookbook_image_image").addClassName("required-entry required-file");
                </script>'
            );
        }
        // @codingStandardsIgnoreEnd

        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'status',
                'required' => true,
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')],
                'value' => 1,
            ]
        );

        $fieldset->addField(
            'store_ids',
            'multiselect',
            [
             'name'     => 'store_ids[]',
             'label'    => __('Store Views'),
             'title'    => __('Store Views'),
             'required' => true,
             'values'   => $this->_systemStore->getStoreValuesForForm(false, true),
            ]
        );

        $customerGroups = $customerGroups = $this->customerGroupCollectionFactory->create()->toOptionArray();

        $fieldset->addField(
            'customer_group_ids',
            'multiselect',
            [
                'name'     => 'customer_group_ids[]',
                'label'    => __('Customer Groups'),
                'title'    => __('Customer Groups'),
                'required' => true,
                'values'   => $customerGroups,
            ]
        );

        $fieldset->addField(
            'show_description',
            'select',
            [
                'label' => __('Show Description'),
                'title' => __('Show Description'),
                'name' => 'status',
                'required' => false,
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'value' => 1,
            ]
        );
        
        $fieldset->addField(
            'image_alt',
            'text',
            [
                'name' => 'image_alt',
                'label' => __('Image Alt'),
                'title' => __('Image Alt'),
            ]
        );

        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);

        $contentField = $fieldset->addField(
            'description',
            'editor',
            [
                'name' => 'description',
                'required' => false,
                'config' => $wysiwygConfig,
            ]
        );

        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element::class
        )->setTemplate(
            'Magento_Cms::page/edit/form/renderer/content.phtml'
        );
        $contentField->setRenderer($renderer);

        $this->_eventManager->dispatch('adminhtml_cms_page_edit_tab_content_prepare_form', ['form' => $form]);
        if (!empty($lookbookId)) {
            $form->setValues($model->getData());
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Lookbook Information');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Lookbook Information');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
