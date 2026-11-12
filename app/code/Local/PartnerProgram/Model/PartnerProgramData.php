<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\GroupRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Psr\Log\LoggerInterface;

/**
 * Reads the live numbers the partner page shows: the customer discount (the program's cart price rule),
 * the shop's commission (the program's tier for the default affiliate group), the commission hold period
 * (affiliate config) and the regular price of each pack size (catalog). Nothing here is a constant.
 */
class PartnerProgramData
{
    public const CACHE_TAG = 'vb_partner_program';

    private const HOLDING_PERIOD_PATH = 'affiliate/commission/holding_period';

    /** @var int[] product IDs read for the price table, used as page cache tags */
    private array $productIds = [];

    public function __construct(
        private readonly ProgramRepository $programRepository,
        private readonly GroupRepository $groupRepository,
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{discount: float, commission_first: float, commission_other: float, uses_per_customer: int}|null
     *         null when the program, its cart rule or its tier is missing, inactive or of a type the page can't explain
     */
    public function getRates(int $programId): ?array
    {
        try {
            $program = $this->programRepository->get($programId);
            if (!$program || !$program->getIsActive()) {
                return $this->fail("program $programId is missing or inactive");
            }

            $rule = $this->ruleRepository->getById($program->getCartRuleId());
            if (!$rule->getIsActive() || $rule->getSimpleAction() !== SalesRule::BY_PERCENT_ACTION) {
                return $this->fail('cart rule ' . $program->getCartRuleId() . ' is inactive or not a percent discount');
            }

            $tier = $this->getDefaultGroupTier($program);
            if (!$tier || $tier->getType() !== ProgramInterface::COMMISSION_TYPE_PERCENT_SUBTOTAL) {
                return $this->fail("program $programId has no percent-of-cart tier for the default group");
            }

            return [
                'discount' => (float)$rule->getDiscountAmount(),
                'commission_first' => (float)$tier->getAmountFirst(),
                'commission_other' => (float)$tier->getAmountOther(),
                'uses_per_customer' => (int)$rule->getUsesPerCustomer(),
            ];
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function getHoldingDays(): int
    {
        return (int)$this->scopeConfig->getValue(self::HOLDING_PERIOD_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Regular price (the `price` attribute, not special or catalog rule prices) of each requested pack size,
     * across the enabled children of the enabled, visible configurable products in the current store.
     *
     * @param string[] $sizeLabels option labels of the size attribute, e.g. ['100g', '250g']
     * @return array<string, array{min: float, max: float}> keyed by label, in the order requested; sizes with
     *         no enabled product are left out
     */
    public function getPackPrices(string $sizeAttribute, array $sizeLabels): array
    {
        try {
            $store = $this->storeManager->getStore();
            $source = $this->eavConfig->getAttribute('catalog_product', $sizeAttribute)->getSource();
            $labelByOption = [];
            foreach ($sizeLabels as $label) {
                $optionId = $source->getOptionId($label);
                if ($optionId) {
                    $labelByOption[(int)$optionId] = $label;
                }
            }
            if (!$labelByOption) {
                return [];
            }

            $parentIds = $this->productCollectionFactory->create()
                ->setStoreId($store->getId())
                ->addStoreFilter($store)
                ->addFieldToFilter('type_id', Configurable::TYPE_CODE)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', ['in' => [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]])
                ->getAllIds();
            if (!$parentIds) {
                return [];
            }

            $connection = $this->resource->getConnection();
            $childIds = $connection->fetchCol(
                $connection->select()
                    ->from($this->resource->getTableName('catalog_product_super_link'), ['product_id'])
                    ->where('parent_id IN (?)', $parentIds)
            );
            if (!$childIds) {
                return [];
            }

            $children = $this->productCollectionFactory->create()
                ->setStoreId($store->getId())
                ->addStoreFilter($store)
                ->addAttributeToSelect(['price', $sizeAttribute])
                ->addIdFilter($childIds)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter($sizeAttribute, ['in' => array_keys($labelByOption)]);

            $prices = [];
            $this->productIds = array_map('intval', $parentIds);
            foreach ($children as $child) {
                $label = $labelByOption[(int)$child->getData($sizeAttribute)] ?? null;
                $price = (float)$child->getData('price');
                if ($label === null || $price <= 0) {
                    continue;
                }
                $this->productIds[] = (int)$child->getId();
                $prices[$label]['min'] = min($prices[$label]['min'] ?? $price, $price);
                $prices[$label]['max'] = max($prices[$label]['max'] ?? $price, $price);
            }

            $ordered = [];
            foreach ($sizeLabels as $label) {
                if (isset($prices[$label])) {
                    $ordered[$label] = $prices[$label];
                }
            }

            return $ordered;
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return [];
        }
    }

    /**
     * @return int[] IDs of the products read by getPackPrices()
     */
    public function getProductIds(): array
    {
        return array_values(array_unique($this->productIds));
    }

    private function getDefaultGroupTier(ProgramInterface $program): ?\Mirasvit\Affiliate\Api\Data\TierInterface
    {
        $tiers = $program->getTiers();
        $defaultGroup = $this->groupRepository->getDefaultGroup();
        if ($defaultGroup) {
            foreach ($tiers as $tier) {
                if ((int)$tier->getGroupId() === (int)$defaultGroup->getId()) {
                    return $tier;
                }
            }
        }

        return $tiers ? reset($tiers) : null;
    }

    private function fail(string $reason): ?array
    {
        $this->logger->warning('Partner program page: ' . $reason);

        return null;
    }
}
