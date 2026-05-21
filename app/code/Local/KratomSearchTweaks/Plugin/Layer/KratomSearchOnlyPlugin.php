<?php

namespace Local\KratomSearchTweaks\Plugin\Layer;

use Magento\Catalog\Model\Layer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;

class KratomSearchOnlyPlugin
{
    private RequestInterface $request;
    private ResourceConnection $resource;
    private ?int $kratomAttributeSetId = null;

    public function __construct(
        RequestInterface $request,
        ResourceConnection $resource
    ) {
        $this->request = $request;
        $this->resource = $resource;
    }

    public function beforePrepareProductCollection(Layer $subject, $collection): array
    {
        // Only enforce on the storefront search results page.
        if ($this->request->getFullActionName() !== 'catalogsearch_result_index') {
            return [$collection];
        }

        $attributeSetId = $this->getKratomAttributeSetId();
        if ($attributeSetId === null) {
            return [$collection];
        }

        // Ensure search results only show kratom products (prevents demo products from appearing).
        try {
            if (method_exists($collection, 'addAttributeToFilter')) {
                $collection->addAttributeToFilter('attribute_set_id', ['eq' => $attributeSetId]);
            } else {
                $collection->addFieldToFilter('attribute_set_id', $attributeSetId);
            }
        } catch (\Throwable $e) {
            // Non-fatal: if filtering fails, don't break the page.
        }

        return [$collection];
    }

    private function getKratomAttributeSetId(): ?int
    {
        if ($this->kratomAttributeSetId !== null) {
            return $this->kratomAttributeSetId;
        }

        $connection = $this->resource->getConnection();
        $entityTypeId = (int)$connection->fetchOne(
            "SELECT entity_type_id FROM {$this->resource->getTableName('eav_entity_type')}
             WHERE entity_type_code = 'catalog_product' LIMIT 1"
        );

        if ($entityTypeId <= 0) {
            $this->kratomAttributeSetId = null;
            return null;
        }

        $attributeSetId = (int)$connection->fetchOne(
            "SELECT attribute_set_id FROM {$this->resource->getTableName('eav_attribute_set')}
             WHERE attribute_set_name = 'Kratom' AND entity_type_id = ? LIMIT 1",
            [$entityTypeId]
        );

        $this->kratomAttributeSetId = $attributeSetId > 0 ? $attributeSetId : null;
        return $this->kratomAttributeSetId;
    }
}

