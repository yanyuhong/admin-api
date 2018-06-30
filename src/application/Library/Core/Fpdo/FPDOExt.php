<?php

namespace App\Library\Core\Fpdo;

class FPDOExt extends \FluentPDO
{
    private $_forceDRDSSlave = false;


    public function from($table, $primaryKey = null)
    {
        $query = new SelectHintQuery($this, $table);
        if ($this->_forceDRDSSlave) {
            $query->setHint(SelectHintQuery::HINT_FORCE_DRDS_SLAVE, true);
        }
        if ($primaryKey !== null) {
            $tableTable     = $query->getFromTable();
            $tableAlias     = $query->getFromAlias();
            $primaryKeyName = $this->structure->getPrimaryKey($tableTable);
            $query          = $query->where("$tableAlias.$primaryKeyName", $primaryKey);
        }

        return $query;
    }

    /**
     * @param bool $switch
     */
    public function setForceDRDSSlave($switch)
    {
        $this->_forceDRDSSlave = $switch;
    }
}
