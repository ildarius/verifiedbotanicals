<?php
/**
 * @author     Magentech.com
 */

namespace Sm\DegreeView\Block;

use Magento\Catalog\Model\Product;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Product
     */
    protected $_product = null;
    protected $helper;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Sm\DegreeView\Helper\Data $helper,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->helper        = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }

    /**
     * @return mixed
     */

    public function getFrameWidth()
    {
        $frameWidth        = $this->helper->getGeneral('default_frame_width');
        $productFrameWidth = $this->getProduct()->getData('sm_degree_width');

        if (!is_null($productFrameWidth)) {
            $frameWidth = $productFrameWidth;
        }

        return $frameWidth;
    }

    /**
     * @return mixed
     */

    public function getFrameHeight()
    {
        $frameHeight        = $this->helper->getGeneral('default_frame_height');
        $productFrameHeight = $this->getProduct()->getData('sm_degree_height');

        if (!is_null($productFrameHeight)) {
            $frameHeight = $productFrameHeight;
        }

        return $frameHeight;
    }

    /**
     * @return string
     */

    public function getFileIndex()
    {
        $mediaSrc     = $this->helper->getMediaUrl();
        $degreeFolder = $this->getProduct()->getData('sm_degree_path') . "/";
        $indexImage   = $this->getProduct()->getData('sm_degree_index');
        $fileIndex    = $mediaSrc . $degreeFolder . $indexImage;
        return $fileIndex;
    }

    /**
     * @return int|void
     */

    public function getCountImage()
    {
        $degreeFolder         = $this->getProduct()->getData('sm_degree_path') . "/";
        $degreeFolderRelative = BP . "/pub/media/" . $degreeFolder;
        $filecount            = 0;
        $files                = glob($degreeFolderRelative . "*.{jpg}", GLOB_BRACE);
        if ($files) {
            $filecount = count($files);
        }

        return $filecount;
    }
}
