<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Store.
 */
class Image extends Column
{
    public const IMAGE_FIELD = 'lookbook_image';

    public const ALT_FIELD = 'image_alt';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ImageFactory
     */
    private $helperImageFactory;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Repository
     */
    protected $assetRepos;

    /**
     * @var SystemStore
     */
    protected $systemStore;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SystemStore $systemStore
     * @param GroupFactory $collectionFactory
     * @param Escaper $escaper
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param ImageFactory $helperImageFactory
     * @param Repository $assetRepos
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemStore $systemStore,
        GroupFactory $collectionFactory,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        ImageFactory $helperImageFactory,
        Repository $assetRepos,
        array $components = [],
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->collectionFactory = $collectionFactory;
        $this->escaper = $escaper;
        $this->_storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->helperImageFactory = $helperImageFactory;
        $this->assetRepos = $assetRepos;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $mediaRelativePath = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $logoPath = $mediaRelativePath . $item[self::IMAGE_FIELD];
                if (!$item[self::IMAGE_FIELD]) {
                    $logoPath = $this->getPlaceHolderImage();
                }
                $item[$fieldName . '_src'] = $logoPath;
                $item[$fieldName . '_alt'] = $this->getAlt($item);
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'lookbook/index/edit',
                    ['lookbook_id' => $item['lookbook_id'], 'store' => $this->context->getRequestParam('store')]
                );
                $item[$fieldName . '_orig_src'] = $logoPath;
            }
        }

        return $dataSource;
    }

    /**
     * Get alt
     *
     * @param array $row
     * @return null|string
     */
    protected function getAlt($row)
    {
        $altField = self::ALT_FIELD;

        return isset($row[$altField]) ? $row[$altField] : null;
    }

    /**
     * Get placeholder image
     *
     * @return string
     */
    private function getPlaceHolderImage()
    {
        $imagePlaceholder = $this->helperImageFactory->create();
        return $this->assetRepos->getUrl($imagePlaceholder->getPlaceholder('small_image'));
    }
}
