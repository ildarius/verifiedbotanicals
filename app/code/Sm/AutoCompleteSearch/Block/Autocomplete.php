<?php

namespace Sm\AutoCompleteSearch\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Sm\AutoCompleteSearch\Helper\Data;

/**
 * Autocomplete class used for transport config data
 */
class Autocomplete extends Template
{
    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * Autocomplete constructor.
     *
     * @param Data $helperData
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Data    $helperData,
        Context $context,
        array   $data = []
    ) {

        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve search delay in milliseconds (500 by default)
     *
     * @return int
     */
    public function getSearchDelay(): int
    {
        return $this->helperData->getSearchDelay();
    }
	 public function getSearchEnable()
    {
        return $this->helperData->getSearchEnable();
    }

    /**
     * Retrieve search action url
     *
     * @return string
     */
    public function getSearchUrl(): string
    {
        return $this->getUrl("sm_searchsuiteautocomplete/ajax/index");
    }
}
