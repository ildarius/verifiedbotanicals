<?php
declare(strict_types=1);

namespace Local\BlogApi\Model;

use Local\BlogApi\Api\BlogPostSyncManagementInterface;
use Local\BlogApi\Api\Data\BlogPostSyncResultInterface;
use Local\BlogApi\Model\Data\BlogPostSyncResult;
use Magefan\Blog\Model\CategoryFactory;
use Magefan\Blog\Model\PostFactory;
use Magefan\Blog\Model\ResourceModel\Post as PostResource;
use Magefan\Blog\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use Magefan\Blog\Model\TagFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Webapi\Rest\Request;

class BlogPostSyncManagement implements BlogPostSyncManagementInterface
{
    /**
     * @var string[]
     */
    private const DIRECT_FIELDS = [
        'title',
        'content',
        'short_content',
        'content_heading',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'og_title',
        'og_description',
        'og_type',
        'author_id',
        'is_active',
        'featured_img_alt',
        'featured_list_img_alt',
    ];

    public function __construct(
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly CategoryFactory $categoryFactory,
        private readonly TagFactory $tagFactory,
        private readonly TagCollectionFactory $tagCollectionFactory,
        private readonly DateTime $dateTime,
        private readonly Request $request,
        private readonly IpWhitelist $ipWhitelist
    ) {
    }

    public function upsert(): BlogPostSyncResultInterface
    {
        $this->ipWhitelist->assertAllowed();

        $requestData = $this->request->getBodyParams();
        $postData = isset($requestData['postData']) && is_array($requestData['postData'])
            ? $requestData['postData']
            : $requestData;

        if (!$postData) {
            throw new InputException(__('The request body is required.'));
        }

        $identifier = trim((string)($postData['identifier'] ?? ''));
        if ($identifier === '') {
            throw new InputException(__('The "identifier" field is required.'));
        }

        $storeIds = array_key_exists('store_ids', $postData)
            ? $this->normalizeIntegerList($postData['store_ids'], true)
            : [0];

        $postId = (int)$this->postResource->checkIdentifier($identifier, $storeIds);
        $post = $this->postFactory->create();
        $isNew = !$postId;

        if ($postId) {
            $this->postResource->load($post, $postId);
        }

        if ($isNew) {
            $this->assertRequiredCreateFields($postData);
            $post->setData('identifier', $identifier);
            $post->setData('store_ids', $storeIds);
            $post->setData('is_active', array_key_exists('is_active', $postData) ? $postData['is_active'] : 1);
            $post->setData(
                'publish_time',
                array_key_exists('publish_time', $postData)
                    ? $this->normalizeDateTime($postData['publish_time'], 'publish_time')
                    : $this->dateTime->gmtDate('Y-m-d H:i:s')
            );
        } elseif (array_key_exists('store_ids', $postData)) {
            $post->setData('store_ids', $storeIds);
        }

        $post->setData('identifier', $identifier);

        foreach (self::DIRECT_FIELDS as $field) {
            if (array_key_exists($field, $postData)) {
                $post->setData($field, $postData[$field]);
            }
        }

        if (array_key_exists('publish_time', $postData)) {
            $post->setData('publish_time', $this->normalizeDateTime($postData['publish_time'], 'publish_time'));
        }

        if (array_key_exists('end_time', $postData)) {
            $post->setData('end_time', $this->normalizeDateTime($postData['end_time'], 'end_time'));
        }

        if ($this->hasCategoryData($postData)) {
            $post->setData('categories', $this->resolveCategoryIds($postData));
        }

        if ($this->hasTagData($postData)) {
            $post->setData('tags', $this->resolveTagIds($postData, (array)$post->getData('store_ids')));
        }

        $this->postResource->save($post);
        $this->postResource->load($post, (int)$post->getId());

        return new BlogPostSyncResult([
            BlogPostSyncResultInterface::STATUS => $isNew ? 'created' : 'updated',
            BlogPostSyncResultInterface::POST_ID => (int)$post->getId(),
            BlogPostSyncResultInterface::IDENTIFIER => (string)$post->getData('identifier'),
            BlogPostSyncResultInterface::TITLE => (string)$post->getData('title'),
            BlogPostSyncResultInterface::POST_URL => (string)$post->getPostUrl(),
            BlogPostSyncResultInterface::STORE_IDS => array_map('intval', (array)$post->getData('store_ids')),
            BlogPostSyncResultInterface::CATEGORY_IDS => array_map('intval', (array)$post->getData('categories')),
            BlogPostSyncResultInterface::TAG_IDS => array_map('intval', (array)$post->getData('tags')),
            BlogPostSyncResultInterface::PUBLISH_TIME => (string)$post->getData('publish_time'),
            BlogPostSyncResultInterface::IS_ACTIVE => (int)$post->getData('is_active'),
            BlogPostSyncResultInterface::META_TITLE => (string)$post->getData('meta_title'),
            BlogPostSyncResultInterface::META_KEYWORDS => (string)$post->getData('meta_keywords'),
            BlogPostSyncResultInterface::META_DESCRIPTION => (string)$post->getData('meta_description'),
            BlogPostSyncResultInterface::OG_TITLE => (string)$post->getData('og_title'),
            BlogPostSyncResultInterface::OG_DESCRIPTION => (string)$post->getData('og_description'),
            BlogPostSyncResultInterface::OG_TYPE => (string)$post->getData('og_type'),
        ]);
    }

    /**
     * @param array $postData
     */
    private function assertRequiredCreateFields(array $postData): void
    {
        foreach (['title', 'content'] as $field) {
            $value = trim((string)($postData[$field] ?? ''));
            if ($value === '') {
                throw new InputException(
                    new Phrase('The "%1" field is required when creating a new blog post.', [$field])
                );
            }
        }
    }

    /**
     * @param array $postData
     * @return int[]
     */
    private function resolveCategoryIds(array $postData): array
    {
        $categoryIds = [];

        if (array_key_exists('category_ids', $postData)) {
            $categoryIds = array_merge(
                $categoryIds,
                $this->normalizeIntegerList($postData['category_ids'])
            );
        }

        if (array_key_exists('category_identifiers', $postData)) {
            foreach ($this->normalizeStringList($postData['category_identifiers']) as $identifier) {
                $category = $this->categoryFactory->create();
                $category->load($identifier);

                if (!$category->getId()) {
                    throw new NoSuchEntityException(
                        __('Blog category with identifier "%1" does not exist.', $identifier)
                    );
                }

                $categoryIds[] = (int)$category->getId();
            }
        }

        return $this->uniqueIntegers($categoryIds);
    }

    /**
     * @param array $postData
     * @param int[] $storeIds
     * @return int[]
     */
    private function resolveTagIds(array $postData, array $storeIds): array
    {
        $tagIds = [];

        if (array_key_exists('tag_ids', $postData)) {
            $tagIds = array_merge(
                $tagIds,
                $this->normalizeIntegerList($postData['tag_ids'])
            );
        }

        if (array_key_exists('tag_identifiers', $postData)) {
            foreach ($this->normalizeStringList($postData['tag_identifiers']) as $identifier) {
                $tag = $this->tagFactory->create();
                $tag->load($identifier);

                if (!$tag->getId()) {
                    throw new NoSuchEntityException(
                        __('Blog tag with identifier "%1" does not exist.', $identifier)
                    );
                }

                $tagIds[] = (int)$tag->getId();
            }
        }

        if (array_key_exists('tag_titles', $postData)) {
            foreach ($this->normalizeStringList($postData['tag_titles']) as $title) {
                $tagCollection = $this->tagCollectionFactory->create();
                $tag = $tagCollection
                    ->addFieldToFilter('title', $title)
                    ->setPageSize(1)
                    ->getFirstItem();

                if (!$tag->getId()) {
                    $tag = $this->tagFactory->create();
                    $tag->setData('title', $title);
                    $tag->setData('is_active', 1);
                    $tag->setData('store_ids', $storeIds ?: [0]);
                    $tag->save();
                }

                $tagIds[] = (int)$tag->getId();
            }
        }

        return $this->uniqueIntegers($tagIds);
    }

    /**
     * @param mixed $value
     * @return int[]
     */
    private function normalizeIntegerList(mixed $value, bool $allowDefaultStore = false): array
    {
        $items = is_array($value) ? $value : [$value];
        $result = [];

        foreach ($items as $item) {
            if ($item === null || $item === '') {
                continue;
            }

            if (is_string($item) && str_contains($item, ',')) {
                $result = array_merge($result, $this->normalizeIntegerList(explode(',', $item), $allowDefaultStore));
                continue;
            }

            if (!is_numeric($item)) {
                throw new InputException(__('Expected an integer list value, got "%1".', (string)$item));
            }

            $result[] = (int)$item;
        }

        $result = $this->uniqueIntegers($result);

        if ($allowDefaultStore && !$result) {
            return [0];
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function normalizeStringList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];
        $result = [];

        foreach ($items as $item) {
            if ($item === null) {
                continue;
            }

            if (is_string($item) && str_contains($item, ',')) {
                $result = array_merge($result, $this->normalizeStringList(explode(',', $item)));
                continue;
            }

            $item = trim((string)$item);
            if ($item !== '') {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param int[] $values
     * @return int[]
     */
    private function uniqueIntegers(array $values): array
    {
        return array_values(array_unique(array_map('intval', $values)));
    }

    private function normalizeDateTime(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable((string)$value))->format('Y-m-d H:i:s');
        } catch (\Exception $exception) {
            throw new InputException(
                new Phrase('The "%1" value "%2" is not a valid date/time.', [$field, (string)$value]),
                $exception
            );
        }
    }

    /**
     * @param array $postData
     */
    private function hasCategoryData(array $postData): bool
    {
        return array_key_exists('category_ids', $postData)
            || array_key_exists('category_identifiers', $postData);
    }

    /**
     * @param array $postData
     */
    private function hasTagData(array $postData): bool
    {
        return array_key_exists('tag_ids', $postData)
            || array_key_exists('tag_identifiers', $postData)
            || array_key_exists('tag_titles', $postData);
    }
}
