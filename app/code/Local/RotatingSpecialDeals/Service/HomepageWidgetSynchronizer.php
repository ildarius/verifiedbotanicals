<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class HomepageWidgetSynchronizer
{
    private RotationConfig $rotationConfig;

    private PageCollectionFactory $pageCollectionFactory;

    private PageFactory $pageFactory;

    private PageResource $pageResource;

    public function __construct(
        RotationConfig $rotationConfig,
        PageCollectionFactory $pageCollectionFactory,
        PageFactory $pageFactory,
        PageResource $pageResource
    ) {
        $this->rotationConfig = $rotationConfig;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->pageFactory = $pageFactory;
        $this->pageResource = $pageResource;
    }

    public function sync(\DateTimeImmutable $endsAt): void
    {
        $pageId = $this->findHomepagePageId();
        $page = $this->pageFactory->create();
        $this->pageResource->load($page, $pageId);

        if (!$page->getId()) {
            throw new LocalizedException(__('The homepage CMS page could not be loaded.'));
        }

        $content = (string)$page->getContent();
        $directive = $this->findDealsDirective($content);
        if ($directive === null) {
            throw new LocalizedException(__('The homepage deals widget directive was not found.'));
        }

        $updatedDirective = $this->setDirectiveParam($directive, 'product_source', 'countdown_products');
        $updatedDirective = $this->setDirectiveParam(
            $updatedDirective,
            'select_category',
            implode(',', $this->rotationConfig->getGroupCategoryIds())
        );
        $updatedDirective = $this->setDirectiveParam(
            $updatedDirective,
            'product_limitation',
            (string)$this->rotationConfig->getHomepageProductLimit()
        );
        $updatedDirective = $this->setDirectiveParam(
            $updatedDirective,
            'date_to',
            $endsAt->format('m/d/Y H:i:s')
        );

        if ($updatedDirective === $directive) {
            return;
        }

        $page->setContent(str_replace($directive, $updatedDirective, $content));
        $this->pageResource->save($page);
    }

    private function findHomepagePageId(): int
    {
        $collection = $this->pageCollectionFactory->create();
        $page = $collection->addFieldToFilter('identifier', $this->rotationConfig->getHomepageIdentifier())
            ->getFirstItem();

        if (!$page->getId()) {
            throw new LocalizedException(
                __('CMS page "%1" was not found.', $this->rotationConfig->getHomepageIdentifier())
            );
        }

        return (int)$page->getId();
    }

    private function findDealsDirective(string $content): ?string
    {
        if (!preg_match_all('/\{\{widget\b.*?\}\}/s', $content, $matches)) {
            return null;
        }

        foreach ($matches[0] as $directive) {
            if (strpos($directive, 'type="' . $this->rotationConfig->getHomepageWidgetType() . '"') === false) {
                continue;
            }

            if (strpos($directive, 'template="' . $this->rotationConfig->getHomepageWidgetTemplate() . '"') === false) {
                continue;
            }

            return $directive;
        }

        return null;
    }

    private function setDirectiveParam(string $directive, string $name, string $value): string
    {
        $escapedValue = str_replace('"', '\"', $value);
        $pattern = '/\b' . preg_quote($name, '/') . '="[^"]*"/';

        if (preg_match($pattern, $directive) === 1) {
            return (string)preg_replace($pattern, $name . '="' . $escapedValue . '"', $directive, 1);
        }

        return str_replace('}}', ' ' . $name . '="' . $escapedValue . '"}}', $directive);
    }
}
