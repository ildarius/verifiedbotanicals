<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Coduzion\Lookbook\Model\ImageUploader;

class Upload extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Coduzion_Lookbook::lookbook_save';
    
    /**
     * @var ImageUploader
     */
    public $imageUploader;

    /**
     * Upload constructor.
     * @param Context $context
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    /**
     * Is access allowed
     *
     * @return mixed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Coduzion_Lookbook::upload');
    }

    /**
     * Execute method
     *
     * @return mixed
     */
    public function execute()
    {
        try {
            $result = $this->imageUploader->saveFileToTmpDir('lookbook_image');
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
