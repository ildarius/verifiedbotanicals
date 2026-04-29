<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Lookbook\Edit\Tab;

use Coduzion\Lookbook\Model\LookbookFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Coduzion\Lookbook\Model\Config\Backend\Image;
use Magento\Store\Model\ScopeInterface;

/**
 * Adminhtml look edit form.
 */
class Markers extends Generic
{
    protected const MARKER_WIDTH = 'lookbook_configuraiton/marker/marker_width';
    protected const MARKER_HEIGHT = 'lookbook_configuraiton/marker/marker_height';
    protected const MARKER_IMAGE = 'lookbook_configuraiton/marker/marker_image';
    protected const FONT_COLOR = 'lookbook_configuraiton/popover/font_color';
    protected const BACKGROUND_COLOR = 'lookbook_configuraiton/popover/background_color';
    protected const FONT_SIZE = 'lookbook_configuraiton/popover/font_size';
    protected const OPACITY = 'lookbook_configuraiton/popover/opacity';
   
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var string
     */
    protected $_template = 'markers.phtml';

    /**
     * @var Lookitems
     */
    private $lookItems;

    /**
     * @var LookbookFactory
     */
    private $lookbookFactory;

    /**
     * @var StoreManagerInterface
     */
    public $_storeManager;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var AdapterFactory
     */
    protected $_imageFactory;

    /**
     * Construct method
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param LookbookFactory $lookbookFactory
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param AdapterFactory $imageFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        LookbookFactory $lookbookFactory,
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        AdapterFactory $imageFactory,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->_productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_imageFactory = $imageFactory;
        $this->lookbookFactory = $lookbookFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Get marker width
     *
     * @return mixed|string
     */
    public function getMarkerWidth()
    {
        $markerWidth = $this->scopeConfig->getValue(
            self::MARKER_WIDTH,
            ScopeInterface::SCOPE_STORE
        );
        return $markerWidth ?? '20';
    }

    /**
     * Get marker height
     *
     * @return mixed|string
     */
    public function getMarkerHeight()
    {
        $markerheight = $this->scopeConfig->getValue(
            self::MARKER_HEIGHT,
            ScopeInterface::SCOPE_STORE
        );
        return $markerheight ?? '20';
    }

    /**
     * Get marker font color
     *
     * @return mixed|string
     */
    public function getMarkerFontColor()
    {
        $fontColor = $this->scopeConfig->getValue(
            self::FONT_COLOR,
            ScopeInterface::SCOPE_STORE
        );
        return $fontColor ?? '#000000';
    }

    /**
     * Get marker image
     *
     * @return mixed
     */
    public function getMarkerImage()
    {
        $lookbookDir = Image::UPLOAD_DIR;
        $mediaUrl = $this->getMediaUrl();
        $markerImage = $this->scopeConfig->getValue(
            self::MARKER_IMAGE,
            ScopeInterface::SCOPE_STORE
        );
        if ($markerImage) {
            return $mediaUrl.$lookbookDir.DIRECTORY_SEPARATOR.$markerImage;
        }
        return $this->getViewFileUrl('Coduzion_Lookbook::images/marker.png');
    }

    /**
     * Get media url
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get font size
     *
     * @return mixed|string
     */
    public function getFontSize()
    {
        $fontSize = $this->scopeConfig->getValue(
            self::FONT_SIZE,
            ScopeInterface::SCOPE_STORE
        );
        return $fontSize ?? '16';
    }

    /**
     * Get marker background color
     *
     * @return mixed|string
     */
    public function getMarkerBgColor()
    {
        $bgColor = $this->scopeConfig->getValue(
            self::BACKGROUND_COLOR,
            ScopeInterface::SCOPE_STORE
        );
        return $bgColor ?? '#ffffff';
    }

    /**
     * Get opacity
     *
     * @return mixed|string
     */
    public function getOpacity()
    {
        $opacity = $this->scopeConfig->getValue(
            self::OPACITY,
            ScopeInterface::SCOPE_STORE
        );
        return $opacity ?? '1';
    }

    /**
     * Get look by id
     *
     * @param int $lookId
     * @return mixed
     */
    public function getLook($lookId)
    {
        $look = $this->lookbookFactory->create()->load($lookId);
        return $look->getData();
    }

    /**
     * Resize the image
     *
     * @param string $image
     * @return string
     */
    public function imageResize($image)
    {
        $absPath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . $image;
        $imageResized = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)
        ->getAbsolutePath('lookbook/resized/') . $image;
        $imageResize = $this->_imageFactory->create();
        $imageResize->open($absPath);
        $imageResize->constrainOnly(false);
        $imageResize->keepTransparency(true);
        $imageResize->keepFrame(true);
        $imageResize->backgroundColor([255, 255, 255]);
        $imageResize->keepAspectRatio(true);
        $imageResize->resize(500, 500);
        $dest = $imageResized;
        $imageResize->save($dest);
        $resizedURL = $this->_storeManager->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'lookbook/resized/' . $image;

        return $resizedURL;
    }
}
