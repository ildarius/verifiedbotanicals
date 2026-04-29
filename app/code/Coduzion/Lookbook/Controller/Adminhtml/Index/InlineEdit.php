<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class InlineEdit extends \Magento\Backend\App\Action
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */

    // @codingStandardsIgnoreStart

    public const ADMIN_RESOURCE = 'Coduzion_Lookbook::lookbook_save';

    // @codingStandardsIgnoreEnd

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Inline edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        
        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $modelid) {
                    /** @var \Coduzion\Lookbook\Model\Lookbook $model */
                    $model = $this->_objectManager->create(\Coduzion\Lookbook\Model\Lookbook::class)->load($modelid);
                    try {
                        // @codingStandardsIgnoreStart
                        $model->setData(array_merge($model->getData(), $postItems[$modelid]));
                        // @codingStandardsIgnoreEnd
                        $model->save();
                    } catch (\Exception $e) {
                        $messages[] = "[Lookbook ID: {$modelid}]  {$e->getMessage()}";
                        $error = true;
                    }
                }
            }
        }
        
        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
