<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Coduzion\Lookbook\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Lookbook extends AbstractDb
{
    // @codingStandardsIgnoreStart
    const TBL_ATT_PRODUCT = 'coduzion_lookbookgrid_rel';
    // @codingStandardsIgnoreEnd

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('coduzion_lookbook_items', 'lookbook_id');
    }
}
