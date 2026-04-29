<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Controller\Adminhtml\Index;

use Magento\Framework\Exception\LocalizedException;
use Coduzion\Lookbook\Model\ImageUploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Coduzion\Lookbook\Model\Lookbook;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    // @codingStandardsIgnoreStart
    public const ADMIN_RESOURCE = 'Coduzion_Lookbook::lookbook_save';
    
    public const LOOKBOOK_MEDIA_DIR = 'lookbook';

    // @codingStandardsIgnoreEnd

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var ImageUploader
     */
    protected $imageUploader;

    /**
     * @var UploaderFactory
     */
    protected $uploader;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

     /**
      * @var ImageUploader
      */
    protected $imageUploaderModel;

    /**
     * Construct method
     *
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param UploaderFactory $uploader
     * @param Filesystem $filesystem
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        UploaderFactory $uploader,
        Filesystem $filesystem,
        ImageUploader $imageUploader
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->imageUploaderModel = $imageUploader;
        $this->_storeManager = $storeManager;
        $this->uploader = $uploader;
        $this->filesystem = $filesystem;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {

        //echo 'snksncksnc'; exit;
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $files = $this->getRequest()->getFiles();

        if ($data) {
            $id = $this->getRequest()->getParam('lookbook_id');
        
            $model = $this->_objectManager->create(Lookbook::class)->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Lookbook no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            
            try {
                if ($files['lookbook_image'] &&
                    isset($files['lookbook_image']['name']) &&
                    !empty($files['lookbook_image']['name'])
                ) {
                    $uploader = $this->uploader->create(['fileId' => 'lookbook_image']);

                    if (isset($uploader->validateFile()['tmp_name']) && $uploader->validateFile()['tmp_name'] != '') {
                        $mediapathget = $this->_storeManager->getStore()
                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                        $uploader = $this->uploader->create(['fileId' => 'lookbook_image']);
                        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                        $uploader->setAllowRenameFiles(true);
                        $uploader->setFilesDispersion(true);
                        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                        $result = $uploader->save($mediaDirectory->getAbsolutePath('lookbook'));
                        $data['lookbook_image'] = self::LOOKBOOK_MEDIA_DIR.$result['file'];
                        $absPath = $mediaDirectory->getAbsolutePath() . $data['lookbook_image'];
                        // @codingStandardsIgnoreStart
                        $imageData = getimagesize($absPath);
                        // @codingStandardsIgnoreEnd
                        $origWidth = $imageData[0];
                        $origHeight = $imageData[1];
                        $data['image_resolution'] = $origWidth .'X'. $origHeight;
                    }
                } else {
                    if (isset($data['lookbook_image']['delete']) && $data['lookbook_image']['delete'] == 1) {
                        $data['lookbook_image'] = '';
                        $data['image_resolution'] = '';
                        $data['image_alt'] = '';
                        $data['markers'] = '';
                    } else {
                        unset($data['lookbook_image']);
                    }
                }

                if (isset($data['customer_group_ids'])) {
                    $customerGroupIds = $data['customer_group_ids'];
                    if (is_array($customerGroupIds)) {
                        $data['customer_group_ids'] = implode(',', $customerGroupIds);
                    }
                }

                if (isset($data['store_ids'])) {
                    $storeIds = $data['store_ids'];
                    if (is_array($storeIds)) {
                        $data['store_ids'] = implode(',', $storeIds);
                    }
                }

                $model->setData($data);
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Lookbook.'));
                $this->dataPersistor->clear('coduzion_lookbook_lookbook');
        
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['lookbook_id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if ($e->getCode() == '666') {
                    if (isset($data['lookbook_image']['delete']) && $data['lookbook_image']['delete'] == 1) {
                        $data['lookbook_image'] = '';
                    } else {
                        unset($data['lookbook_image']);
                    }
                } else {
                    // @codingStandardsIgnoreStart
                    $this->messageManager->addError($e->getMessage());
                    return $resultRedirect->setPath('*/*/edit', ['lookbook_id' => $this->getRequest()->getParam('lookbook_id')]);
                }
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Lookbook.'));
            }
        
            $this->dataPersistor->set('coduzion_lookbook_lookbook', $data);
            return $resultRedirect->setPath('*/*/edit', ['lookbook_id' => $this->getRequest()->getParam('lookbook_id')]);
        }
        // @codingStandardsIgnoreEnd
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Get image data
     *
     * @param object $model
     * @param array $data
     * @return mixed
     */
    public function imageData($model, $data)
    {
        if ($model->getId()) {
            $pageData = $this->_objectManager->create(Lookbook::class)->load($model->getId());
            if (isset($data['lookbook_image'][0]['name'])) {
                $imageName1 = $pageData->getThumbnail();
                $imageName2 = $data['lookbook_image'][0]['name'];
                if ($imageName1 != $imageName2) {
                    $imageUrl = $data['lookbook_image'][0]['url'];
                    $imageName = $data['lookbook_image'][0]['name'];
                    $data['lookbook_image'] = $this->imageUploaderModel->saveMediaImage($imageName, $imageUrl);
                } else {
                    $data['lookbook_image'] = $data['lookbook_image'][0]['name'];
                }
            } else {
                $data['lookbook_image'] = '';
            }
        } else {
            if (isset($data['lookbook_image'][0]['name'])) {
                $imageUrl = $data['lookbook_image'][0]['url'];
                $imageName = $data['lookbook_image'][0]['name'];
                $data['lookbook_image'] = $this->imageUploaderModel->saveMediaImage($imageName, $imageUrl);
            }
        }
        $model->setData($data);
        return $model;
    }
}
