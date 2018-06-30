<?php
namespace App\Library\Help;

class Sql
{
    const RAW_STR_PREFIX = '&/';
    const RAW_STR_NO_ESCAPE_PREFIX = '&/!';

    const LOGIC = '__logic';

    private $rawStrPrefixLength;
    /** @var  \PDO */
    private $_pdo;

    private function __construct($pdo)
    {
        $this->rawStrPrefixLength = strlen(self::RAW_STR_PREFIX);
        $this->rawStrNoEscapePrefixLength = strlen(self::RAW_STR_NO_ESCAPE_PREFIX);
        $this->_pdo = $pdo;
    }

    public static function getInstance($pdo)
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self($pdo);
        }
        return $instance;
    }

    /**
     * SQL 原始字符包装，如 CURRENT_TIMESTAMP, field + 1
     */
    public static function rawValue($val, $escapeIt = TRUE)
    {
        return ($escapeIt ? self::RAW_STR_PREFIX : self::RAW_STR_NO_ESCAPE_PREFIX) . $val;
    }

    public static function rawName($name, $escapeIt = TRUE)
    {
        return ($escapeIt ? self::RAW_STR_PREFIX : self::RAW_STR_NO_ESCAPE_PREFIX) . $name;
    }

    //去除字段名和值中的特殊前缀
    public function trimData($data)
    {
        $trimData = array();
        foreach ($data as $key => $value) {
            if (strpos($key, self::RAW_STR_NO_ESCAPE_PREFIX) === 0) {
                $key = substr($key, $this->rawStrNoEscapePrefixLength);
            } else if (strpos($key, self::RAW_STR_PREFIX) === 0) {
                $key = substr($key, $this->rawStrPrefixLength);
            }

            if (strpos($value, self::RAW_STR_NO_ESCAPE_PREFIX) === 0) {
                $value = substr($value, $this->rawStrNoEscapePrefixLength);
            }
            if (strpos($value, self::RAW_STR_PREFIX) === 0) {
                $value = substr($value, $this->rawStrPrefixLength);
            }

            $trimData[$key] = $value;
        }

        return $trimData;
    }

    /**
     * 单行数据
     * insert(array(
     *   'key1' => 'val1',
     *   'key2' => '&/CURRENT_TIMESTAMP',
     * ));
     *
     * output : (`key1`,`key2`) VALUES ('val1',CURRENT_TIMESTAMP)
     *
     * 多行数据
     * insert(array(
     *    'key1', 'key2',
     * ), array(
     *      array('val11', 'val12'),
     *      array('val21', 'val22')
     * ));
     * output: (`key1`,`key2`) VALUES ('val11','val12'),('val21','val22')
     * 
     * @param string $row
     * @param null $rowsData
     *
     * @return string
     */
    public function insert($row, $rowsData = NULL)
    {
        if ($rowsData) {
            $keys = $row;
        } else {
            $keys = array_keys($row);
            $rowsData = array(array_values($row));
        }

        $keySql = '(' . implode(',', array_map(array($this, '_escapeName'), $keys)) . ')';

        $valSqls = array();
        foreach ($rowsData as $data) {
            $valSqls[] =
                '(' . implode(',', array_map(array($this, '_escapeValue'), $data)) . ')';
        }
        $valSql = implode(',', $valSqls);

        return " $keySql VALUES $valSql";
    }

    /**
     * @example
     * update(array(
     *   'key1' => 'value1',
     *   'key2' => '&/CURRENT_TIMESTAMP',
     * ));
     * output: " (`key1`,`key2`) VALUES ('value1',CURRENT_TIMESTAMP)"
     *
     * @param array $data
     *
     * @return string
     */
    public function update($data)
    {
        $sql = '';
        foreach ($data as $name => $val) {
            $name = $this->_escapeName($name);
            $val = $this->_escapeValue($val);
            $sql .= "$name=$val,";
        }
        return ' SET ' . trim($sql, ',');
    }

    /**
     * @example
     * replace(array(
     *    'key1' => 'value1',
     *    'key2' => '&/CURRENT_TIMESTAMP',
     * ), array(
     *    'key1' => '&/key1 + 1'
     * ));
     *
     * output: " (`key1`,`key2`) VALUES ('value1',CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `key1`=key1 + 1"
     *
     * @param array $insData same as method insert parameter
     * @param array $resData replace data
     *
     * @return string
     */
    public function replace($insData, $resData = NULL)
    {
        if ($resData === NULL) {
            $resData = $insData;
        }

        $sql = $this->insert($insData);

        if (empty($resData)) {
            return $sql;
        }

        $sql .= ' ON DUPLICATE KEY UPDATE ';
        $sql .= preg_replace('@^\s*SET\s*@', '', $this->update($resData));
        return $sql;
    }

    /**
     * @example
     *
     * example 1.
     * where(array(
     *   'key1' => 'value1',
     *   'key2' => NULL,
     *   'key3' => array('!=' => 'value3'),
     *   'key4' => array('value4_1', 'value4_2')
     * ));
     *
     * output : WHERE `key1`='value1' AND `key2` is NULL AND `key3` != 'value3' AND (`key4` = 'value4_1' OR `key4` = 'value4_2')
     *
     * example 2.
     * where(array(
     *    array('key1' => array('like' => '%value1%')),
     *    array(
     *          'key2' => 3,
     *          'key3' => 4,
     *    )
     * ), array(
     *   'order_by' => 'id DESC',
     *   'offset' => 10,
     *   'limit' => 20,
     * ));
     *
     * output: WHERE (`key1` like '%value1%') OR (`key2`='3' AND `key3`='4') ORDER BY id DESC LIMIT 10, 20
     *
     * @param array $where 条件数组,默认是AND关系,数字索引数组(非关系数组)表示OR关系
     * @param array $attrs 可设置的值:order_by,group_by,limit,offset
     *
     * @return string
     */
    public function where($where, $attrs = array())
    {
        $sql = '';
        if (!empty($where)) {
            $whereSql = $this->_where($where);
            if ($whereSql) {
                $sql .= ' WHERE ' . $whereSql;
            }
        }
        if ($attrs) {
            if (isset($attrs['group_by'])) {
                $sql .= ' GROUP BY ' . $attrs['group_by'];
            }

            if (isset($attrs['having'])) {
                $sql .= ' HAVING ' . $attrs['having'];
            }

            if (isset($attrs['order_by'])) {
                $sql .= ' ORDER BY ' . $attrs['order_by'];
            }

            if (!empty($attrs['offset']) || !empty($attrs['limit'])) {
                $sql .= ' LIMIT ';
                if (isset($attrs['offset'])) {
                    $sql .= $attrs['offset'] . ',';
                }

                if (isset($attrs['limit'])) {
                    $sql .= $attrs['limit'];
                }
            }
        }

        return $sql;
    }

    private function _where($where)
    {
        if (empty($where) || !is_array($where)) {
            return '';
        }

        $logic = '';

        if (isset($where[self::LOGIC])) {
            $logic = $where[self::LOGIC];
            unset($where[self::LOGIC]);
        }

        $isArray = self::_isArray($where);
        if ($isArray) {
            $conds = array_map(array($this, '_where'), $where);
            $conds = array_map(array($this, '_wrapWithBrackets'), array_filter($conds));
            if (!$logic) {
                $logic = 'OR';
            }
            $sql = implode(" $logic ", $conds);
            return $sql;
        }

        $conds = array();
        foreach ($where as $key => $val) {
            $conds[] = $this->_cond($key, $val);
        }
        if (!$logic) {
            $logic = 'AND';
        }
        $sql = implode(" $logic ", array_filter($conds));
        return $sql;
    }

    private function _cond($name, $val, $inIteration = FALSE)
    {
        if (!$inIteration) {
            $name = $this->_escapeName($name);
        }

        if (!is_array($val)) {
            $val = $this->_escapeValue($val);
            if ($val === 'NULL') {
                return "$name is NULL";
            }
            return "$name=$val";
        }

        $logic = 'OR';
        if (isset($val[self::LOGIC])) {
            $logic = $val[self::LOGIC];
            unset($val[self::LOGIC]);
        }

        if (self::_isHash($val)) {
            if (count($val) == 1) {
                $_k = array_keys($val);
                $operation = array_pop($_k);
                $val = $this->_escapeValue($val[$operation]);
                return "{$name} {$operation} {$val}";
            } else {
                $newVal = array();
                foreach ($val as $iKey => $iVal) {
                    $newVal[] = array($iKey => $iVal);
                }
                $val = $newVal;
            }
        }

        $conds = array();
        foreach ($val as $condVal) {
            if (self::_isArray($condVal)) {
                //array('val1', 'val2', ...)
                $conds[] = $this->_cond($name, $condVal, TRUE);
                continue;
            } else if (self::_isHash($condVal)) {
                //array('!=' => 'val')
                $_k = array_keys($condVal);
                $operation = array_pop($_k);
                $condVal = $condVal[$operation];
            } else {
                $operation = '=';
            }
            $condVal = $this->_escapeValue($condVal);
            $conds[] = "{$name} {$operation} {$condVal}";
        }

        if (empty($conds)) {
            return "$name = ''";
        }

        return '(' . implode(" $logic ", $conds) . ')';
    }

    private function _wrapWithBrackets($str)
    {
        return '(' . $str . ')';
    }

    //是不是纯数字索引
    private static function _isArray($val)
    {
        if (!is_array($val)) {
            return FALSE;
        }
        $keys = array_keys($val);
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private static function _isHash($val)
    {
        return is_array($val) && !self::_isArray($val);
    }

    private function _escapeValue($str)
    {
        if ($str === NULL) {
            return 'NULL';
        }
        if (strpos($str, self::RAW_STR_NO_ESCAPE_PREFIX) === 0) {
            return substr($str, $this->rawStrNoEscapePrefixLength);
        }
        if (strpos($str, self::RAW_STR_PREFIX) === 0) {
            return $this->escape(substr($str, $this->rawStrPrefixLength));
        }
        return $this->escape($str, true);
    }

    private function _escapeName($str)
    {
        if ($str[0] == ':') {
            return substr($str, 1);
        }
        if (strpos($str, self::RAW_STR_NO_ESCAPE_PREFIX) === 0) {
            return substr($str, $this->rawStrNoEscapePrefixLength);
        }
        if (strpos($str, self::RAW_STR_PREFIX) === 0) {
            return $this->escape(substr($str, $this->rawStrPrefixLength));
        }
//        $str = $this->escape($str);
        return "`$str`";
    }

    public function escape($str, $addQuote = FALSE)
    {
        return $this->es($str, $addQuote);
    }

    public function es($str, $addQuote = FALSE)
    {
        $str = $this->_pdo->quote($str);
        if (!$addQuote) {
            return substr($str, 1, -1);
        }
        return $str;
    }
}
