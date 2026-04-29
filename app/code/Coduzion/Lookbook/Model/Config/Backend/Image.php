<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Image as ParentImage;
use Magento\Framework\App\RequestInterface;

class Image extends ParentImage
{
    /**
     * Media directory sub path
     */
    public const UPLOAD_DIR = 'lookbook';

    /**
     * Upload max file size in kilobytes
     *
     * @var int
     */
    protected $_maxFileSize = 2048;
    
   /**
    * Return path to directory for upload file
    *
    * @return string
    * @throw \Magento\Framework\Exception\LocalizedException
    */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg'];
    }

    /**
     * Save uploaded file before saving config value
     *
     * Save changes and delete file if "delete" option passed
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $deleteFlag = is_array($value) && !empty($value['delete']);
        if ($this->isTmpFileAvailable($value) && $imageName = $this->getUploadedImageName($value)) {
            $fileTmpName = $this->getTmpFileName();
            if ($this->getOldValue() && ($fileTmpName || $deleteFlag)) {
                $this->_mediaDirectory->delete(self::UPLOAD_DIR . '/' . $this->getOldValue());
            }
        }
        return parent::beforeSave();
    }

    /**
     * Check if temporary file is available for new image upload.
     *
     * @param array $value
     * @return bool
     */
    private function isTmpFileAvailable($value)
    {
        return is_array($value) && isset($value[0]['tmp_name']);
    }
    
    /**
     * Gets image name from $value array.
     *
     * Will return empty string in a case when $value is not an array.
     *
     * @param array $value Attribute value
     * @return string
     */
    private function getUploadedImageName($value)
    {
        if (is_array($value) && isset($value[0]['name'])) {
            return $value[0]['name'];
        }
        return '';
    }
}
