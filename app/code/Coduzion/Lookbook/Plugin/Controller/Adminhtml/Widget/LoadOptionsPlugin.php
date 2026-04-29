<?php
/**
 *
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Plugin\Controller\Adminhtml\Widget;

use Magento\Framework\App\ObjectManager;
use Magento\Widget\Controller\Adminhtml\Widget\LoadOptions;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\Helper\Data;

class LoadOptionsPlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * Construct method
     *
     * @param RequestInterface $request
     * @param Data $jsonHelper
     */
    public function __construct(
        RequestInterface $request,
        Data $jsonHelper
    ) {
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Ajax responder for loading plugin options form
     *
     * @param LoadOptions $subject
     * @return $proceed
     */
    public function beforeExecute(LoadOptions $subject)
    {
        if ($paramsJson = $this->request->getParam('widget')) {
            $request = $this->jsonHelper->jsonDecode($paramsJson);
            // @codingStandardsIgnoreStart
            if (is_array($request)) {
                if (isset($request['widget_type']) && $request['widget_type'] == 'Coduzion\Lookbook\Block\Widget\LookbooksList') {
                    $request['values']['added_time'] = time();
                }
            }
            // @codingStandardsIgnoreEnd

            $returnJson = $this->jsonHelper->jsonEncode($request);
            $requestWithAddedTime = $this->request->setParam('widget', $returnJson);
            return [$requestWithAddedTime];
        }
        return [];
    }
}
