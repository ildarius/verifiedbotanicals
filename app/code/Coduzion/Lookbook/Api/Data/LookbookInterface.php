<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Api\Data;

interface LookbookInterface
{

    public const UPDATE_TIME = 'update_time';
    public const STATUS = 'status';
    public const NAME = 'title';
    public const LOOKBOOK_ID = 'lookbook_id';
    public const CREATION_TIME = 'creation_time';
    public const IMAGE_ALT = 'image_alt';
    public const IMAGE = 'lookbook_image';
    public const DESCRIPTION = 'description';

    /**
     * Get lookbook_id
     *
     * @return string|null
     */
    public function getLookbookId();

    /**
     * Set lookbook_id
     *
     * @param string $lookbookId
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setLookbookId($lookbookId);

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     *
     * @param string $description
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setDescription($description);

    /**
     * Get image
     *
     * @return string|null
     */
    public function getLookbookImage();

    /**
     * Set image
     *
     * @param string $image
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setLookbookImage($image);

    /**
     * Get image_alt
     *
     * @return string|null
     */
    public function getImageAlt();

    /**
     * Set image_alt
     *
     * @param string $imageAlt
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setImageAlt($imageAlt);

    /**
     * Get creation_time
     *
     * @return string|null
     */
    public function getCreationTime();

    /**
     * Set creation_time
     *
     * @param string $creationTime
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setCreationTime($creationTime);

    /**
     * Get update_time
     *
     * @return string|null
     */
    public function getUpdateTime();

    /**
     * Set update_time
     *
     * @param string $updateTime
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setUpdateTime($updateTime);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Coduzion\Lookbook\Lookbook\Api\Data\LookbookInterface
     */
    public function setStatus($status);
}
