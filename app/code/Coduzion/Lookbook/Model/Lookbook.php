<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model;

use Coduzion\Lookbook\Api\Data\LookbookInterface;
use Magento\Framework\Model\AbstractModel;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook as LookbookResource;

class Lookbook extends AbstractModel implements LookbookInterface
{
    /**
     * Product cache tag
     */
    public const CACHE_TAG = 'lookbook_c';

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(LookbookResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getLookbookId()
    {
        return $this->getData(self::LOOKBOOK_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLookbookId($lookbookId)
    {
        return $this->setData(self::LOOKBOOK_ID, $lookbookId);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getLookbookImage()
    {
        return $this->getData(self::IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setLookbookImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    /**
     * @inheritDoc
     */
    public function getImageAlt()
    {
        return $this->getData(self::IMAGE_ALT);
    }

    /**
     * @inheritDoc
     */
    public function setImageAlt($imageAlt)
    {
        return $this->setData(self::IMAGE_ALT, $imageAlt);
    }

    /**
     * @inheritDoc
     */
    public function getCreationTime()
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setCreationTime($creationTime)
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * @inheritDoc
     */
    public function getUpdateTime()
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setUpdateTime($updateTime)
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}
