<?php

namespace App\Library\Core\Fpdo;

class SelectHintQuery extends \SelectQuery
{
    const HINT_FORCE_DRDS_SLAVE = 'fpdo.tddl.slave';

    private $_hints = [];

    protected function buildQuery()
    {
        $sql = parent::buildQuery();
        if ( !empty($this->_hints[self::HINT_FORCE_DRDS_SLAVE])) {
            return '/*TDDL:SLAVE*/' . $sql;
        }
        return $sql;
    }

    public function setHint($name, $value)
    {
        $this->_hints[$name] = $value;
    }
}
