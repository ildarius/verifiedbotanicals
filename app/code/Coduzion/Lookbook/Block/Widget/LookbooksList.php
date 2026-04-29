<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Widget;

use Magento\Framework\View\Element\Template;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook\CollectionFactory as LookbookCollectionFactory;
use Coduzion\Lookbook\Model\Rule;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Rule\Model\Condition\Combine;
use Coduzion\Lookbook\Model\Rule\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Block\BlockInterface;
use Magento\Widget\Helper\Conditions;
use Coduzion\Lookbook\Model\Lookbook;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Coduzion\Lookbook\Model\Config\Backend\Image;

/**
 * Coduzion Lookbook List widget block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class LookbooksList extends Template implements BlockInterface, IdentityInterface
{
    /**
     * Prefix for cache key of Lookbook
     */
    public const CACHE_KEY_PREFIX = 'CODUZION_LOOKBOOK_';

    protected const IS_ENABLED = 'lookbook_configuraiton/general/is_enabled';
    protected const MARKER_WIDTH = 'lookbook_configuraiton/marker/marker_width';
    protected const MARKER_HEIGHT = 'lookbook_configuraiton/marker/marker_height';
    protected const MARKER_IMAGE = 'lookbook_configuraiton/marker/marker_image';
    protected const FONT_COLOR = 'lookbook_configuraiton/popover/font_color';
    protected const BACKGROUND_COLOR = 'lookbook_configuraiton/popover/background_color';
    protected const FONT_SIZE = 'lookbook_configuraiton/popover/font_size';
    protected const OPACITY = 'lookbook_configuraiton/popover/opacity';
    protected const INFINITE_LOOP = 'lookbook_configuraiton/slider/infinite_loop';
    protected const NEXT_PREVIOUS = 'lookbook_configuraiton/slider/next_previous';
    protected const DOTS = 'lookbook_configuraiton/slider/dots';
    protected const AUTO_PLAY = 'lookbook_configuraiton/slider/autoplay';
    protected const SPEED = 'lookbook_configuraiton/slider/speed';
    protected const DEFAULT_LOOKBOOKS_COUNT = 10;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var LookbookCollectionFactory
     */
    protected $lookbookCollectionFactory;

    /**
     * @var SqlBuilder
     */
    protected $sqlBuilder;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var Conditions
     */
    protected $conditionsHelper;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var AdapterFactory
     */
    protected $_imageFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Template\Context $context
     * @param LookbookCollectionFactory $lookbookCollectionFactory
     * @param HttpContext $httpContext
     * @param SqlBuilder $sqlBuilder
     * @param File $fileDriver
     * @param Rule $rule
     * @param Conditions $conditionsHelper
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param AdapterFactory $imageFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     * @param Json|null $json
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Template\Context $context,
        LookbookCollectionFactory $lookbookCollectionFactory,
        HttpContext $httpContext,
        SqlBuilder $sqlBuilder,
        File $fileDriver,
        Rule $rule,
        Conditions $conditionsHelper,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        AdapterFactory $imageFactory,
        ScopeConfigInterface $scopeConfig,
        array $data = [],
        Json $json = null
    ) {
        $this->lookbookCollectionFactory = $lookbookCollectionFactory;
        $this->httpContext = $httpContext;
        $this->sqlBuilder = $sqlBuilder;
        $this->fileDriver = $fileDriver;
        $this->rule = $rule;
        $this->conditionsHelper = $conditionsHelper;
        $this->storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_imageFactory = $imageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        parent::__construct($context, $data);
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        $conditions = $this->getData('conditions')
            ? $this->getData('conditions')
            : $this->getData('conditions_encoded');

        return [
            'CODUZION_LOOKBOOK_LIST_WIDGET',
            '',
            $this->storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            (int)$this->getRequest()->getParam($this->getData('page_var_name'), 1),
            $this->getLookbooksPerPage(),
            $this->getLookbooksCount(),
            $conditions,
            $this->json->serialize($this->getRequest()->getParams()),
            $this->getTemplate(),
            $this->getTitle()
        ];
    }

    /**
     * Is lookbook module enabled
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return $this->scopeConfig->getValue(
            self::IS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get block cache life time
     *
     * @return int
     * @since 100.1.0
     */
    protected function getCacheLifetime()
    {
        return 3600;
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
        return $this->getViewFileUrl('Coduzion_Lookbook::images/marker-pin.svg');
    }

    /**
     * Get media url
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
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
     * Is auto play enabled
     *
     * @return bool
     */
    public function isAutoPlayEnabled()
    {
        return $this->scopeConfig->getValue(
            self::AUTO_PLAY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get slider speed
     *
     * @return mixed|string
     */
    public function getSliderSpeed()
    {
        $speed = $this->scopeConfig->getValue(
            self::SPEED,
            ScopeInterface::SCOPE_STORE
        );
        return $speed ?? '300';
    }

    /**
     * Show dots
     *
     * @return bool
     */
    public function showDots()
    {
        $dots = $this->scopeConfig->getValue(
            self::DOTS,
            ScopeInterface::SCOPE_STORE
        );
        return $dots ? true : false;
    }

    /**
     * Show next previous button
     *
     * @return bool
     */
    public function showNextPrev()
    {
        $nextPrev = $this->scopeConfig->getValue(
            self::NEXT_PREVIOUS,
            ScopeInterface::SCOPE_STORE
        );
        return $nextPrev;
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
     * Is slider set to infinite
     *
     * @return bool
     */
    public function isSliderInfinite()
    {
        $infinite = $this->scopeConfig->getValue(
            self::INFINITE_LOOP,
            ScopeInterface::SCOPE_STORE
        );
        return $infinite ? true : false;
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
     * @inheritdoc
     */
    protected function _beforeToHtml()
    {
        return $this->setLookbookCollection($this->createCollection());
    }

    /**
     * Resize the image
     *
     * @param string $image
     * @param int $width
     * @param int $height
     * @return string
     */
    public function imageResize($image, $width = null, $height = null)
    {
        $imageWidth = $width ? $width.'/': '';
        $imageResized = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)
        ->getAbsolutePath('lookbook/resized/') . $imageWidth. $image;
        if ($this->fileDriver->isExists($imageResized)) {
            return $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'lookbook/resized/' . $imageWidth. $image;
        }
        $absPath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . $image;
        // @codingStandardsIgnoreStart
        $imageData = getimagesize($absPath);
        // @codingStandardsIgnoreEnd
        $origWidth = $imageData[0];
        $origHeight = $imageData[1];
        $height = $width/$origWidth*$origHeight;

        $imageResize = $this->_imageFactory->create();
        $imageResize->open($absPath);
        $imageResize->constrainOnly(false);
        $imageResize->keepTransparency(true);
        $imageResize->keepFrame(true);
        $imageResize->backgroundColor([255, 255, 255]);
        $imageResize->keepAspectRatio(true);
        $imageResize->resize($width, $height);
        $imageResize->save($imageResized);
        $resizedURL = $this->storeManager->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'lookbook/resized/' . $imageWidth. $image;

        return $resizedURL;
    }

    /**
     * Prepare and return product collection
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @throws LocalizedException
     */
    public function createCollection()
    {

        /** @var $collection Collection */
        $collection = $this->lookbookCollectionFactory->create();

        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($collection);
        
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        
        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);
        $collection->addFieldToFilter('status', 1);
        
        return $collection;
    }

    /**
     * Get lookbook collection
     *
     * @return \Coduzion\Lookbook\Block\Widget\Collection
     */
    public function getLookbookCollection()
    {
        $collection = $this->createCollection();
        return $collection;
    }

    /**
     * Get conditions
     *
     * @return Combine
     */
    protected function getConditions()
    {
        $conditions = $this->getData('conditions_encoded')
            ? $this->getData('conditions_encoded')
            : $this->getData('conditions');

        if ($conditions) {
            $conditions = $this->conditionsHelper->decode($conditions);
        }

        $this->rule->loadPost(['conditions' => $conditions]);
        return $this->rule->getConditions();
    }

    /**
     * Retrieve how many products should be displayed
     *
     * @return int
     */
    public function getLookbooksCount()
    {
        if ($this->hasData('lookbooks_count')) {
            return $this->getData('lookbooks_count');
        }

        if (null === $this->getData('lookbooks_count')) {
            $this->setData('lookbooks_count', self::DEFAULT_LOOKBOOKS_COUNT);
        }

        return $this->getData('lookbooks_count');
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        if ($this->getLookbookCollection()) {
            foreach ($this->getLookbookCollection() as $lookbook) {
                if ($lookbook instanceof IdentityInterface) {
                    $identities[] = $lookbook->getIdentities();
                }
            }
        }
        $identities = array_merge([], ...$identities);

        return $identities ?: [Lookbook::CACHE_TAG];
    }

    /**
     * Get value of widgets' title parameter
     *
     * @return mixed|string
     */
    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * Get value of widgets' instance_id parameter
     *
     * @return int
     */
    public function getAddedTime()
    {
        return $this->getData('added_time');
    }
}
