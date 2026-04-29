<?php
namespace Sm\Categories\Block\Widget;

use Magento\Widget\Block\BlockInterface;

class AddCategories extends \Sm\Categories\Block\Categories implements BlockInterface {

    /**
     * AddCategories constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param array $data
     * @param null $attr
     *
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        array $data = [],
        $attr = null
    ) {
        parent::__construct($context, $categoryFactory, $categoryHelper, $categoryRepository, $data, $attr);
    }

}