<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Block;

use Coduzion\Lookbook\Model\LookbookFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Element\Template\Context;

class Lookbook extends \Magento\Framework\View\Element\Template
{
    /**
     * Prefix for cache key of Lookbook
     */
    public const CACHE_KEY_PREFIX = 'CODUZION_LOOKBOOK_';
    
    /**
     * @var string
     */
    protected $_template = 'lookbook.phtml';

    /**
     * @var LookbookFactory
     */
    protected $lookbookFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var AdapterFactory
     */
    protected $_imageFactory;

    /**
     * Constructor method
     *
     * @param Context $context
     * @param LookbookFactory $lookbookFactory
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param AdapterFactory $imageFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        LookbookFactory $lookbookFactory,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        AdapterFactory $imageFactory,
        array $data = []
    ) {
        $this->lookbookFactory = $lookbookFactory;
        $this->_filesystem = $filesystem;
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        parent::__construct($context, $data);
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
     * Get lookbook collection
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getLookbookCollection()
    {
        $lookbookCollection = $this->lookbookFactory->create()->getCollection();
        return $lookbookCollection;
    }

    /**
     * Get lookbook by id
     *
     * @param int $id
     * @return $this
     */
    public function getLookbookById($id)
    {
        $lookbook = $this->lookbookFactory->create()->load($id);
        return $lookbook;
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
