<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Lookbook\Widget;

use Magento\Rule\Model\Condition\AbstractCondition;
use Coduzion\Lookbook\Model\Rule;
use Magento\Backend\App\Action\Context;

class Conditions extends \Coduzion\Lookbook\Controller\Adminhtml\Lookbook\Widget
{
    /**
     * @var \Coduzion\Lookbook\Model\Rule
     */
    protected $rule;

    /**
     * @param Context $context
     * @param Rule $rule
     */
    public function __construct(
        Context $context,
        Rule $rule
    ) {
        $this->rule = $rule;
        parent::__construct($context);
    }

    /**
     * Execute method
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $typeData = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $className = $typeData[0];

        $model = $this->_objectManager->create($className)
            ->setId($id)
            ->setType($className)
            ->setRule($this->rule)
            ->setPrefix('conditions');

        if (!empty($typeData[1])) {
            $model->setAttribute($typeData[1]);
        }

        $result = '';
        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $model->getValueAfterElementHtml();
            $result = $model->asHtmlRecursive();
        }
        $this->getResponse()->setBody($result);
    }
}
