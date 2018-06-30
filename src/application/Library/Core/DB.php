<?php

namespace App\Library\Core;

use App\Library\Exception\ExceptionDB;
use App\Library\Help\Sql;
use Monolog\Logger;

class DB {
    private static $_instance;
    /** @var  Sql */
    private static $_sqlHelper;
    protected $_table;
    protected $_dbname;
    private static $_forceMaster = false;
    /** @var  Logger */
    private static $_logger;

    public static $transactions;

    const SINGLE_INSTANCE_PREFIX = 'single_';

    private $FPDO;
    private $FPDO_SLAVE;

    public function __construct($table, $dbname = '') {
        if(!$dbname) {
            $dbname = \Yaf_Application::app()->getConfig()->get('db');
        }
        $this->_table = $table;
        $this->_dbname = $dbname;
    }

    /**
     * 获取FPDO实例
     * @return FPDO
     */
    public function FPDO() {
        if(!$this->FPDO) {
            $this->FPDO = new FPDO($this->getPdo(), $this->_table);
        }
        return $this->FPDO;
    }

    /**
     * 获取FPDO实例
     * @return FPDO
     */
    public function FPDO_SLAVE() {
        if(!$this->FPDO_SLAVE) {
            $globalShardConfig = \Yaf_Registry::get('config')['db'];
            if(isset($globalShardConfig['schema'][$this->_dbname]['slave'])) {
                $selectedNodeName = $globalShardConfig['schema'][$this->_dbname]['slave'];
            }
            else {
                $selectedNodeName = $globalShardConfig['schema'][$this->_dbname]['default'];
            }
            $config = RegBox()->Config()->get('db')['nodes'][$selectedNodeName];
            $PDO = new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', $config['host'], $config['port'], $config['database_name']),
                $config['username'],
                $config['password'],
                array(
                    \PDO::ATTR_ERRMODE                  => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
                    \PDO::ATTR_EMULATE_PREPARES         => TRUE,
                    \PDO::ATTR_TIMEOUT                  => 10,
                    \PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES \'UTF8\'',
                )
            );
            $this->FPDO_SLAVE = new FPDO($PDO, $this->_table);
            $this->FPDO_SLAVE->DBInstance()->setForceDRDSSlave(true);
        }
        return $this->FPDO_SLAVE;
    }

    private function _getDBInstance($shardKey, $useSlave = false) {
        $db = self::getDBInstance($this->_table, $this->_dbname, $shardKey, $useSlave);
        self::$_sqlHelper = Sql::getInstance($db);
        self::$_logger = RegBox()->Log();
        return $db;
    }

    static public function getDBInstance($table, $db, $shardKey, $useSlave = false) {
        $db = self::getDBConn($table, $db, $shardKey, $useSlave);
        return $db;
    }

    static public function setForceMaster($v) {
        self::$_forceMaster = $v;
    }

    private static function _newPDO($host, $port, $db, $user, $password) {
        /** @var Logger $log */
        $log = RegBox()->Log();
        try {
            return new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db),
                $user,
                $password,
                array(
                    \PDO::ATTR_ERRMODE                  => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
                    \PDO::ATTR_EMULATE_PREPARES         => TRUE,
                    \PDO::ATTR_TIMEOUT                  => 10,
                    \PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES \'UTF8MB4\'',
                )
            );
        } catch(\PDOException $e) {
            $msg = "DBError:[Server:{$host}:{$port}/{$db}][Msg:" . $e->getMessage() . "]";
            $log->crit($msg);
            throw $e;
        }
    }

    /**
     * @param string $table
     * @param string $db
     * @param array $shardKey
     * @param bool $useSlave
     * @return \PDO
     * @throws ExceptionDB
     */
    private static function getDBConn($table, $db, $shardKey = [], $useSlave = false) {
        $globalShardConfig = \Yaf_Registry::get('config')['db'];
        if(!isset($globalShardConfig['schema'][$db]['shard'][$table])) {
            $instanceName = self::SINGLE_INSTANCE_PREFIX . $db;
            if(isset(self::$_instance[$instanceName])) {
                return self::$_instance[$instanceName];
            }
            $selectedNodeName = $globalShardConfig['schema'][$db]['default'];
            if(!self::$_forceMaster && $useSlave && isset($globalShardConfig['schema'][$db]['readers'])) {
                $pool = $globalShardConfig['schema'][$db]['readers'];
            }
            else {
                $pool = $globalShardConfig['schema'][$db]['proxies'];
            }
            $totalWeights = array_sum($pool);
            $weight = rand(1, $totalWeights);
            $weightLine = 0;
            foreach($pool as $serverName => $serverWeight) {
                $weightLine += $serverWeight;
                if($weightLine >= $weight) {
                    $selectedNodeName = $serverName;
                    break;
                }
            }

            //连接失败后，依次尝试所有节点
            try {
                $nodeConfig = $globalShardConfig['nodes'][$selectedNodeName];
                self::$_instance[$instanceName] = self::_newPDO($nodeConfig['host'], $nodeConfig['port'], $nodeConfig['database_name'],
                    $nodeConfig['username'], $nodeConfig['password']);
            } catch(\PDOException $e) {
                foreach($pool as $serverName => $serverWeight) {
                    $nodeConfig = $globalShardConfig['nodes'][$serverName];
                    try {
                        self::$_instance[$instanceName] = self::_newPDO($nodeConfig['host'], $nodeConfig['port'], $nodeConfig['database_name'],
                            $nodeConfig['username'], $nodeConfig['password']);
                        break;
                    } catch(\PDOException $e) {
                        continue;
                    }
                }
            }
            return self::$_instance[$instanceName];
        }

        $hashConfig = $globalShardConfig['schema'][$db]['shard'][$table];
        if(!isset($shardKey[$hashConfig['key']])) {
            throw new ExceptionDB('分表键不存在');
        }

        $mod = $shardKey[$hashConfig['key']] % $hashConfig['mod'];
        $numAccu = 0;
        $node_select_idx = null;
        foreach($hashConfig['locations'] as $idx => $tableNum) {
            $numAccu += $tableNum;
            if($mod < $numAccu) {
                $node_select_idx = $idx;
                break;
            }
        }
        if($node_select_idx === null) {
            throw new ExceptionDB('无法映射到表');
        }
        $nodeName = $hashConfig['nodes'][$node_select_idx];
        if(isset(self::$_instance[$nodeName])) {
            return self::$_instance[$nodeName];
        }
        $nodeConfig = $globalShardConfig['nodes'][$nodeName];
        self::$_instance[$nodeName] = self::_newPDO($nodeConfig['host'], $nodeConfig['port'], $nodeConfig['database_name'],
            $nodeConfig['username'], $nodeConfig['password']);
        return self::$_instance[$nodeName];
    }

    /**
     * @desc
     * @param array $where
     * @param array $attrs
     * @param array $shardKey
     * @param string $hint :mysql hint,例如'force index(i_MemberId)',解决某些sql查询优化器走错索引的问题
     * @return boolean|array:
     */
    public function select($where = [], $attrs = [], $shardKey = [], $hint = '') {
        if($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDBInstance($shardKey, true);
        if(!$db) {
            return FALSE;
        }
        //todo:drds modify by wangfei. remove hint
        $hint = '';


        $selectFields = isset($attrs['select']) ? $attrs['select'] : '*';

        $sql = "SELECT {$selectFields} FROM " . $this->getTableName($this->_table, $this->_dbname, $shardKey) . " {$hint} " . self::$_sqlHelper->where($where, $attrs);
        $res = NULL;

        try {
            $query = $db->query($sql);
        } catch(\PDOException $e) {
            $msg = "DBError:[DB:{$this->_dbname}/{$this->_table}][Sql:{$sql}][Msg:" . $e->getMessage() . "]";
            self::$_logger->error($msg);
            throw $e;
        }
        if($sql === FALSE) {
            return FALSE;
        }

        $result = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function selectOne($where = array(), $attrs = array(), $shardKey = []) {
        $attrs['limit'] = 1;
        $attrs['offset'] = 0;

        $res = $this->select($where, $attrs, $shardKey);
        if($res === FALSE) {
            return FALSE;
        }
        if(empty($res)) {
            return NULL;
        }
        return $res[0];
    }

    public function selectCount($where = array(), $attrs = array()) {
        if(!isset($attrs['select'])) {
            $attrs['select'] = 'COUNT(1)';
        }
        $attrs['select'] .= ' AS `total`';

        $res = $this->select($where, $attrs);
        if($res === FALSE) {
            return FALSE;
        }
        return isset($res[0]['total']) ? intval($res[0]['total']) : FALSE;
    }

    /**
     * 获得数据 同时 统计页码
     * @param array $where
     * @param array $attrs
     * @param bool $needCount
     * @return array
     */
    public function selectAndCount($where = array(), $attrs = array(), $needCount = false) {
        $data = [];
        $data['data'] = $this->select($where, $attrs);
        $limitDefault = 20;
        if(isset($attrs['limit'])) {
            $limitArr = explode(',', $attrs['limit']);
            $limit = array_pop($limitArr);
            if($limit <= 0) {
                $limit = $limitDefault;
            }
        }
        else {
            $limit = $limitDefault;
        }
        $count = $this->selectCount($where);
        $data['total_page'] = ceil($count / $limit);
        if($needCount) {
            $data['total_count'] = $count;
        }
        return $data;
    }

    public function insert($insArr = [], $returnLastId = TRUE, $shardKey = []) {
        $db = $this->_getDBInstance($shardKey, false);
        $sql = 'INSERT INTO ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->insert($insArr);
        $ret = $this->mod($db, $sql);
        if($ret === FALSE) {
            return FALSE;
        }

        $lastId = 0;
        if($returnLastId) {
            $lastId = $db->lastInsertId();
        }

        return $returnLastId ? $lastId : $ret;
    }

    public function insertIgnore($insArr = [], $returnLastId = TRUE, $shardKey = []) {
        $db = $this->_getDBInstance($shardKey, false);
        $sql = 'INSERT IGNORE INTO ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->insert($insArr);
        $ret = $this->mod($db, $sql);
        if($ret === FALSE) {
            return FALSE;
        }

        $lastId = 0;
        if($returnLastId) {
            $lastId = $db->lastInsertId();
        }

        return $returnLastId ? $lastId : $ret;
    }

    public function insertMany($insKeys = [], $insArr = [], $returnLastId = FALSE, $shardKey = []) {
        $db = $this->_getDBInstance($shardKey);
        $sql = 'INSERT INTO ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->insert($insKeys, $insArr);
        $ret = $this->mod($db, $sql);
        if($ret === FALSE) {
            return FALSE;
        }

        $lastId = 0;
        if($returnLastId) {
            $lastId = $db->lastInsertId();
        }

        return $returnLastId ? $lastId : $ret;
    }

    public function insertUpdate($insArr, $updateArr = NULL, $shardKey = []) {
        if($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDBInstance($shardKey, false);
        if(!$db) {
            return FALSE;
        }


        $sql = 'INSERT INTO ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->replace($insArr, $updateArr);

        $ret = $this->mod($db, $sql);
        //        $this->_lastSql = $sql;

        if($ret === FALSE) {
            return FALSE;
        }


        return $ret;
    }

    public function insertManyUpdate($insKeys, $insArr, $updateArr = NULL, $shardKey = []) {
        if($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDBInstance($shardKey, false);
        if(!$db) {
            return FALSE;
        }

        $sql = 'INSERT INTO ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->insert($insKeys, $insArr);
        $sql .= ' ON DUPLICATE KEY UPDATE ';
        $sql .= preg_replace('@^\s*SET\s*@', '', self::$_sqlHelper->update($updateArr));

        $ret = $this->mod($db, $sql);
        //        $this->_lastSql = $sql;

        if($ret === FALSE) {
            return FALSE;
        }

        return $ret;
    }

    public function update($where = [], $updateArr = [], $shardKey = []) {
        $db = $this->_getDBInstance($shardKey, false);
        $sql = 'UPDATE ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->update($updateArr) . self::$_sqlHelper->where($where);
        return $this->mod($db, $sql);
    }

    public function delete($where = [], $shardKey = []) {
        $db = $this->_getDBInstance($shardKey, false);
        $sql = 'DELETE FROM ' . $this->getTableName($this->_table, $this->_dbname, $shardKey) . self::$_sqlHelper->where($where);
        return $this->mod($db, $sql);
    }

    public function query($table = '', $dbName = '', $shardKey = [], $sql = '') {
        $db = self::getDBInstance($table, $dbName, $shardKey, false);
        return $db->query($sql);
    }

    public function exec($table = '', $dbName = '', $shardKey = [], $sql = '') {
        $db = self::getDBInstance($table, $dbName, $shardKey, false);
        return $db->exec($sql);
    }

    /**
     * @param \PDO $dbInstance
     * @param string $sql
     * @return mixed
     * @throws \PDOException
     */
    public function mod($dbInstance, $sql) {
        //        return $dbInstance->exec($sql);
        try {
            self::$_forceMaster = true;
            return $dbInstance->exec($sql);
        } catch(\PDOException $e) {
            $msg = "DBError:[DB:{$this->_dbname}/{$this->_table}][Sql:{$sql}][Msg:" . $e->getMessage() . "]";
            self::$_logger->error($msg);
            throw $e;
        }
    }

    public function getFullyTableName() {
        return $this->_dbname . '.' . $this->_table;
    }

    /** 获取表名，类似内部的 selectOne */
    public function getTableNameDefault($shardKey = []) {
        return $this->getTableName($this->_table, $this->_dbname, $shardKey);
    }
    public function getTableName($table = '', $dbName = '', $shardKey = []) {
        $globalShardConfig = \Yaf_Registry::get('cfg_database');
        //不用分表
        if(!isset($globalShardConfig['schema'][$dbName]['shard'][$table])) {
            return $table;
        }
        $hashConfig = $globalShardConfig['schema'][$dbName]['shard'][$table];
        if(!isset($shardKey[$hashConfig['key']])) {
            throw new ExceptionDB('分表键不存在');
        }
        $mod = $shardKey[$hashConfig['key']] % $hashConfig['mod'];
        $numAccu = 0;
        $node_select_idx = null;
        foreach($hashConfig['locations'] as $idx => $tableNum) {
            $numAccu += $tableNum;
            if($mod < $numAccu) {
                $node_select_idx = $idx;
                break;
            }
        }
        if($node_select_idx === null) {
            throw new ExceptionDB('无法映射到表');
        }
        return sprintf("{$table}_%04d", $mod);
    }

    public function compileSavepoint($name) {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string $name
     * @return string
     */
    public function compileSavepointRollBack($name) {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }


    public function supportsSavePoints() {
        //开启需慎重,不常用
        return false;
    }


    public function beginTransaction() {
        if(!isset(self::$transactions[$this->_dbname])) {
            self::$transactions[$this->_dbname] = 0;
        }
        ++self::$transactions[$this->_dbname];

        if(self::$transactions[$this->_dbname] == 1) {
            try {
                $this->getPdo()->beginTransaction();
            } catch(\Exception $e) {
                --self::$transactions[$this->_dbname];

                throw $e;
            }
        }
        elseif(self::$transactions[$this->_dbname] > 1 && $this->supportsSavePoints()) {
            $this->getPdo()->exec(
                $this->compileSavepoint('trans' . self::$transactions[$this->_dbname])
            );
        }
    }

    public function commit() {
        if(self::$transactions[$this->_dbname] == 1) {
            $this->getPdo()->commit();
            $this->getPdo()->exec('set autocommit=1');
        }

        --self::$transactions[$this->_dbname];

    }

    public function rollback() {
        if(self::$transactions[$this->_dbname] == 1) {
            $this->getPdo()->rollBack();
            $this->getPdo()->exec('set autocommit=1');
        }
        elseif(self::$transactions[$this->_dbname] > 1 && $this->supportsSavePoints()) {
            $this->getPdo()->exec(
                $this->compileSavepointRollBack('trans' . self::$transactions[$this->_dbname])
            );
        }

        self::$transactions[$this->_dbname] = max(0, self::$transactions[$this->_dbname] - 1);
    }

    public function getPdo() {
        return self::getDBConn($this->_table, $this->_dbname, []);
    }

    public function lastInsertId($shardKey = []) {
        $db = $this->_getDBInstance($shardKey);
        return $db->lastInsertId();
    }
}

