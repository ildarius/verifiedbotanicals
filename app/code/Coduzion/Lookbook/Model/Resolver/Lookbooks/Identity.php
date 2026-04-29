<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model\Resolver\Lookbooks;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Magento\Framework\App\Config;

class Identity implements IdentityInterface
{

    /**
     * @var string
     */
    private $cacheTag = Config::CACHE_TAG;

    /**
     * Get Identities
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids =  empty($resolvedData['name']) ?
            [] : [$this->cacheTag, sprintf('%s_%s', $this->cacheTag, $resolvedData['name'])];
        return $ids;
    }
}
