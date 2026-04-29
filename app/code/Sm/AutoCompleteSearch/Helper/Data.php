<?php

namespace Sm\AutoCompleteSearch\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Search Suite Autocomplete config data helper
 */
class Data extends AbstractHelper
{
    /**
     * XML config path search delay
     */
	const XML_PATH_SEARCH_ENABLE = 'sm_autocompletesearch/autocompletesearch_main/search_enable'; 
    const XML_PATH_SEARCH_DELAY = 'sm_searchsuite/searchsuiteautocomplete_main/search_delay';

    /**
     * XML config path autocomplete fields
     */
    const XML_PATH_AUTOCOMPLETE_FIELDS = 'sm_searchsuite/searchsuiteautocomplete_main/autocomplete_fields';

    /**
     * XML config path suggest results number
     */
    const XML_PATH_SUGGESTED_RESULT_NUMBER = 'sm_searchsuite/searchsuiteautocomplete_main/suggested_result_number';

    /**
     * XML config path product results number
     */
    const XML_PATH_PRODUCT_RESULT_NUMBER = 'sm_searchsuite/searchsuiteautocomplete_main/product_result_number';

    /**
     * XML config path product result fields
     */
    const XML_PATH_PRODUCT_RESULT_FIELDS = 'sm_searchsuite/searchsuiteautocomplete_main/product_result_fields';

    /**
     * Retrieve search delay
     *
     * @param int|null $storeId
     * @return int
     */
	 public function getSearchEnable($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SEARCH_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    public function getSearchDelay(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_SEARCH_DELAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve list of autocomplete fields
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAutocompleteFieldsAsArray(?int $storeId = null): array
    {
        return explode(',', $this->getAutocompleteFields($storeId));
    }

    /**
     * Retrieve comma-separated autocomplete fields
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAutocompleteFields(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_AUTOCOMPLETE_FIELDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve suggest results number
     *
     * @param int|null $storeId
     * @return int
     */
    public function getSuggestedResultNumber(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_SUGGESTED_RESULT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve product results number
     *
     * @param int|null $storeId
     * @return int
     */
    public function getProductResultNumber(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_RESULT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve list of product result fields
     *
     * @param int|null $storeId
     * @return array
     */
    public function getProductResultFieldsAsArray(?int $storeId = null): array
    {
        return explode(',', $this->getProductResultFields($storeId));
    }

    /**
     * Retrieve comma-separated product result fields
     *
     * @param int|null $storeId
     * @return string
     */
    public function getProductResultFields(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_RESULT_FIELDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
