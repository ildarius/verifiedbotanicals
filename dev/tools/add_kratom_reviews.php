<?php

declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Review\Model\Rating;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManagerInterface;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Throwable $exception) {
}

$reviewData = [
    'Red Bali' => [
        ['nickname' => 'Megan Brooks', 'title' => 'Consistent Batch', 'detail' => 'Rich color and very consistent grind.', 'created_at' => '2026-03-03 10:14:00'],
        ['nickname' => 'Jason Miller', 'title' => 'Clean Packaging', 'detail' => 'Packaging arrived clean and professional.', 'created_at' => '2026-03-06 13:25:00'],
        ['nickname' => 'Lauren Hayes', 'title' => 'Easy To Reference', 'detail' => 'Lab information was easy to reference.', 'created_at' => '2026-03-10 09:42:00'],
        ['nickname' => 'Derek Collins', 'title' => 'Reliable Quality', 'detail' => 'Good batch consistency compared to prior orders.', 'created_at' => '2026-03-14 16:08:00'],
    ],
    'Red Maeng Da' => [
        ['nickname' => 'Ashley Turner', 'title' => 'Fresh Appearance', 'detail' => 'Fine powder texture and fresh appearance.', 'created_at' => '2026-03-24 11:05:00'],
        ['nickname' => 'Brandon Reed', 'title' => 'As Described', 'detail' => 'The product looked exactly as described.', 'created_at' => '2026-03-27 15:18:00'],
        ['nickname' => 'Samantha Price', 'title' => 'Professional Packaging', 'detail' => 'Impressed with the labeling and sealed packaging.', 'created_at' => '2026-03-31 12:37:00'],
        ['nickname' => 'Tyler Brooks', 'title' => 'Multiple Sizes', 'detail' => 'Reliable quality across multiple sizes.', 'created_at' => '2026-04-03 17:11:00'],
    ],
    'Red Hulu / Red Kapuas' => [
        ['nickname' => 'Nicole Foster', 'title' => 'Smooth Consistency', 'detail' => 'Nice deep tone and smooth powder consistency.', 'created_at' => '2026-04-14 10:22:00'],
        ['nickname' => 'Kevin Ross', 'title' => 'Clearly Labeled', 'detail' => 'Well-packaged and clearly labeled.', 'created_at' => '2026-04-17 14:49:00'],
        ['nickname' => 'Emily Carter', 'title' => 'Research Comparison', 'detail' => 'A solid option for research comparison.', 'created_at' => '2026-04-21 09:16:00'],
        ['nickname' => 'Justin Perry', 'title' => 'Clean Batch', 'detail' => 'The batch looked clean and uniform.', 'created_at' => '2026-04-24 18:03:00'],
    ],
    'Green Maeng Da' => [
        ['nickname' => 'Hannah Cooper', 'title' => 'Bright Color', 'detail' => 'Bright green color and finely milled texture.', 'created_at' => '2026-04-28 11:41:00'],
        ['nickname' => 'Ryan Bennett', 'title' => 'Bag To Bag', 'detail' => 'Very consistent from bag to bag.', 'created_at' => '2026-05-01 13:09:00'],
        ['nickname' => 'Rachel Morgan', 'title' => 'Quick Fulfillment', 'detail' => 'Professional presentation and quick fulfillment.', 'created_at' => '2026-05-05 08:54:00'],
        ['nickname' => 'Ethan Parker', 'title' => 'Organized Labeling', 'detail' => 'Clear labeling makes it easy to organize.', 'created_at' => '2026-05-08 16:26:00'],
    ],
    'Green Malay' => [
        ['nickname' => 'Olivia Simmons', 'title' => 'Clean Aroma', 'detail' => 'Fresh-looking powder with a clean aroma.', 'created_at' => '2026-05-05 10:33:00'],
        ['nickname' => 'Nathan Brooks', 'title' => 'Research Documentation', 'detail' => 'Great consistency for research documentation.', 'created_at' => '2026-05-08 15:02:00'],
        ['nickname' => 'Chloe Sanders', 'title' => 'Excellent Condition', 'detail' => 'Sealed well and arrived in excellent condition.', 'created_at' => '2026-05-12 09:28:00'],
        ['nickname' => 'Adam Foster', 'title' => 'Premium Quality', 'detail' => 'Quality feels premium compared to other samples.', 'created_at' => '2026-05-15 17:40:00'],
    ],
    'Green Hulu / Green Kapuas' => [
        ['nickname' => 'Madison Kelly', 'title' => 'Uniform Texture', 'detail' => 'Uniform texture and appealing natural color.', 'created_at' => '2026-05-12 11:18:00'],
        ['nickname' => 'Jacob Ward', 'title' => 'Durable Packaging', 'detail' => 'The packaging feels durable and professional.', 'created_at' => '2026-05-14 14:12:00'],
        ['nickname' => 'Abigail Morris', 'title' => 'Green Comparison', 'detail' => 'Good choice for comparing green vein samples.', 'created_at' => '2026-05-16 09:47:00'],
        ['nickname' => 'Luke Bennett', 'title' => 'Clean Presentation', 'detail' => 'Consistent grind and clean presentation.', 'created_at' => '2026-05-18 18:21:00'],
    ],
];

$targetStoreIds = getEnglishStoreIds($objectManager);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$reviewFactory = $objectManager->get(ReviewFactory::class);
$ratingCollection = $objectManager->create(Rating::class)->getCollection();

foreach ($ratingCollection as $rating) {
    $rating->setStores($targetStoreIds)->setIsActive(1)->save();
}

$ratingOptionIds = getHighestRatingOptionIds($objectManager, $ratingCollection);

$created = 0;
$skipped = 0;

foreach ($reviewData as $productName => $reviews) {
    $product = findProductByName($productName, $productRepository);
    if ($product === null) {
        echo "Skipped missing product: {$productName}\n";
        continue;
    }

    foreach ($reviews as $reviewRow) {
        if (reviewExists($objectManager, (int)$product->getId(), $reviewRow['detail'])) {
            $skipped++;
            syncReviewTimestamp($objectManager, (int)$product->getId(), $reviewRow['detail'], $reviewRow['created_at']);
            echo "Skipped existing review for {$productName}: {$reviewRow['detail']}\n";
            continue;
        }

        /** @var Review $review */
        $review = $reviewFactory->create();
        $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
            ->setEntityPkValue((int)$product->getId())
            ->setStatusId(Review::STATUS_APPROVED)
            ->setStoreId((int)$targetStoreIds[0])
            ->setStores($targetStoreIds)
            ->setNickname($reviewRow['nickname'])
            ->setTitle($reviewRow['title'])
            ->setDetail($reviewRow['detail'])
            ->save();

        syncReviewTimestamp($objectManager, (int)$product->getId(), $reviewRow['detail'], $reviewRow['created_at']);

        foreach ($ratingOptionIds as $ratingId => $optionId) {
            $rating = $objectManager->create(Rating::class)->load($ratingId);
            $rating->setReviewId((int)$review->getId())
                ->addOptionVote($optionId, (int)$product->getId());
        }

        $review->aggregate();
        $created++;
        echo "Created review for {$productName} by {$reviewRow['nickname']}\n";
    }
}

echo "\nSummary\n";
echo "Created: {$created}\n";
echo "Skipped existing: {$skipped}\n";

function getEnglishStoreIds(ObjectManagerInterface $objectManager): array
{
    /** @var StoreManagerInterface $storeManager */
    $storeManager = $objectManager->get(StoreManagerInterface::class);
    $storeIds = [];
    foreach ($storeManager->getStores() as $store) {
        if ($store->getId() === 0) {
            continue;
        }

        if (stripos((string)$store->getName(), 'English') === false) {
            continue;
        }

        $storeIds[] = (int)$store->getId();
    }

    sort($storeIds);

    return $storeIds;
}

function getHighestRatingOptionIds(ObjectManagerInterface $objectManager, $ratingCollection): array
{
    $map = [];
    foreach ($ratingCollection as $rating) {
        $option = $objectManager->create(Option::class)
            ->getCollection()
            ->addRatingFilter((int)$rating->getId())
            ->setOrder('value', 'DESC')
            ->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();

        if ($option && $option->getId()) {
            $map[(int)$rating->getId()] = (int)$option->getId();
        }
    }

    return $map;
}

function findProductByName(string $name, ProductRepositoryInterface $productRepository)
{
    $skuMap = [
        'Red Bali' => 'RB',
        'Red Maeng Da' => 'RMD',
        'Red Hulu / Red Kapuas' => 'RH',
        'Green Maeng Da' => 'GMD',
        'Green Malay' => 'GM',
        'Green Hulu / Green Kapuas' => 'GH',
    ];

    if (!isset($skuMap[$name])) {
        return null;
    }

    try {
        return $productRepository->get($skuMap[$name], false, null, true);
    } catch (NoSuchEntityException $exception) {
        return null;
    }
}

function reviewExists(ObjectManagerInterface $objectManager, int $productId, string $detail): bool
{
    $connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
    $result = $connection->fetchOne(
        "SELECT r.review_id
        FROM review r
        JOIN review_detail rd ON rd.review_id = r.review_id
        WHERE r.entity_pk_value = ?
          AND rd.detail = ?
        LIMIT 1",
        [$productId, $detail]
    );

    return $result !== false;
}

function syncReviewTimestamp(
    ObjectManagerInterface $objectManager,
    int $productId,
    string $detail,
    string $createdAt
): void {
    $connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
    $reviewId = $connection->fetchOne(
        "SELECT r.review_id
        FROM review r
        JOIN review_detail rd ON rd.review_id = r.review_id
        WHERE r.entity_pk_value = ?
          AND rd.detail = ?
        LIMIT 1",
        [$productId, $detail]
    );

    if ($reviewId === false) {
        return;
    }

    $connection->update(
        'review',
        ['created_at' => $createdAt],
        ['review_id = ?' => (int)$reviewId]
    );
}
