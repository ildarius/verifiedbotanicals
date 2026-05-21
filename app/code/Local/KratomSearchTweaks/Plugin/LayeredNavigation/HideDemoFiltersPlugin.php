<?php

namespace Local\KratomSearchTweaks\Plugin\LayeredNavigation;

use Magento\Framework\App\RequestInterface;
use Magento\LayeredNavigation\Block\Navigation;

class HideDemoFiltersPlugin
{
    private RequestInterface $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function afterGetFilters(Navigation $subject, array $result): array
    {
        // Only hide on storefront search results page.
        if ($this->request->getFullActionName() !== 'catalogsearch_result_index') {
            return $result;
        }

        $hidden = [
            'color' => true,
            'size' => true,
            'manufacturer' => true,
        ];

        $filtered = [];
        foreach ($result as $filter) {
            try {
                $requestVar = (string)$filter->getRequestVar();
            } catch (\Throwable $e) {
                $filtered[] = $filter;
                continue;
            }

            if (isset($hidden[$requestVar])) {
                continue;
            }

            $filtered[] = $filter;
        }

        return $filtered;
    }
}

