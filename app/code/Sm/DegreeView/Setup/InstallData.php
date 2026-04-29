<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product description block
 *
 * @author     Magentech.com
 */

namespace Sm\DegreeView\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'sm_degree_path', [
            'group' => '360 Degree',
            'type' => 'text',
            'sort_order' => 22,
            'backend' => '',
            'frontend' => '',
            'label' => '360 Degree Path',
            'input' => 'text',
            'class' => '',
            'source' => '',
            'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
        ]);

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'sm_degree_index', [
            'group' => '360 Degree',
            'type' => 'text',
            'sort_order' => 25,
            'backend' => '',
            'frontend' => '',
            'label' => 'Image Index',
            'input' => 'text',
            'class' => '',
            'source' => '',
            'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
        ]);

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'sm_degree_width', [
            'group' => '360 Degree',
            'type' => 'text',
            'sort_order' => 30,
            'backend' => '',
            'frontend' => '',
            'label' => 'Frame Width',
            'input' => 'text',
            'class' => '',
            'source' => '',
            'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
        ]);

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'sm_degree_height', [
            'group' => '360 Degree',
            'type' => 'text',
            'sort_order' => 35,
            'backend' => '',
            'frontend' => '',
            'label' => 'Frame Height',
            'input' => 'text',
            'class' => '',
            'source' => '',
            'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
        ]);
    }

}
