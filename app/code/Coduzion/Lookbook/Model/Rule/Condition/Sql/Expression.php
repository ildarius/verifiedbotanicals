<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Model\Rule\Condition\Sql;

/**
 * Abstract Rule sql condition
 */
class Expression extends \Zend_Db_Expr
{
    /**
     * Turn expression in this object into string
     *
     * @return string
     */
    public function __toString()
    {
        return empty($this->_expression) ? '' : '(' . $this->_expression . ')';
    }
}
