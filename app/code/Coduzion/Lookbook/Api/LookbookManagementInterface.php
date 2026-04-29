<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Api;

interface LookbookManagementInterface
{

    /**
     * GET for Lookbook api
     *
     * @param string $param
     * @return string
     */
    public function getLookbook($param);
}
