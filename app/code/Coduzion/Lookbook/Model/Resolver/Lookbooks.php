<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Lookbooks implements ResolverInterface
{

    /**
     * @var Lookbooks
     */
    private $lookbooksDataProvider;

    /**
     * @param DataProvider\Lookbooks $lookbooksDataProvider
     */
    public function __construct(
        DataProvider\Lookbooks $lookbooksDataProvider
    ) {
        $this->lookbooksDataProvider = $lookbooksDataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $lookbooksData = $this->lookbooksDataProvider->getLookbooks();
        return $lookbooksData;
    }
}
