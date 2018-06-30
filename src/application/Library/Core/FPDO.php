<?php
namespace App\Library\Core;

use App\Library\Core\Fpdo\FPDOExt;
use App\Library\Core\Fpdo\SelectHintQuery;
use Monolog\Logger;
use App\Library\Exception\ExceptionDB;

class FPDO
{
	/** @var  Logger */
	private static $_logger;

	private static $_instance;

	private static $_transcation = 0;

	const SINGLE_INSTANCE_PREFIX = 'single_';

	private static $_forceMaster = false;

	private $FPDO;
	private $PDO;
	private $_table;
	private $_dbname;

	public function __construct($PDO, $table, $dbname = '')
	{
		if ( !$dbname) {
			$dbname = \Yaf_Application::app()->getConfig()->get('db');
		}
		$this->PDO = $PDO;
		$this->_table = $table;
		$this->_dbname = $dbname;
	}

	/**
	 * 获取FluentPDO实例
	 * @return FPDOExt
	 */
	public function DBInstance()
	{
		if (!$this->FPDO) {
			$this->FPDO = new FPDOExt($this->getPdo());
		}
		return $this->FPDO;
	}

	/**
	 * 获取SelectQuery实例
	 * @return SelectHintQuery
	 */
	public function query()
	{
		return $this->DBInstance()->from($this->_table);
	}

	/**
	 * 获取InsertQuery实例
	 *
	 * @param $values
	 *
	 * @return \InsertQuery
	 */
	public function insert($values)
	{
		return $this->DBInstance()->insertInto($this->_table, $values);
	}

	/**
	 * 获取UpdateQuery实例
	 *
	 * @param array $set
	 * @param null  $primaryKey
	 *
	 * @return \UpdateQuery
	 */
	public function update($set = array(), $primaryKey = null)
	{
		return $this->DBInstance()->update($this->_table, $set, $primaryKey);

	}

	/**
	 * 获取DeleteQuery实例
	 *
	 * @param null $primaryKey
	 *
	 * @return \DeleteQuery
	 */
	public function delete($primaryKey = null)
	{
		return $this->DBInstance()->delete($this->_table, $primaryKey);
	}

	/**
	 * 获取PDO实例
	 * @return \PDO
	 * @throws ExceptionDB
	 */
	public function getPdo()
	{
		return $this->PDO;
	}

	/**
	 * 直接执行SQL
	 *
	 * @param $sql
	 *
	 * @return array
	 */
	public function execSql($sql)
	{
		$pdo = $this->getPdo();
		$sth = $pdo->prepare($sql);
		$sth->execute();
	}

	/**
	 * 直接执行SQL获取数据
	 *
	 * @param $sql
	 *
	 * @return array
	 */
	public function querySql($sql)
	{
		$pdo = $this->getPdo();
		$sth = $pdo->prepare($sql);
		$sth->execute();
		$data = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $data;
	}

	/**
	 * @param string $table
	 * @param string $db
	 * @param array $shardKey
	 * @param bool $useSlave
	 * @return \PDO
	 * @throws ExceptionDB
	 */
	private static function getDBConn($table, $db, $shardKey = [], $useSlave = false)
	{
		$globalShardConfig = \Yaf_Registry::get('config')['db'];
		if (!isset($globalShardConfig['schema'][$db]['shard'][$table])) {
			$instanceName = self::SINGLE_INSTANCE_PREFIX . $db;
			if (isset(self::$_instance[$instanceName])) {
				return self::$_instance[$instanceName];
			}
			$defaultNodeName = $selectedNodeName = $globalShardConfig['schema'][$db]['default'];
			if (!self::$_forceMaster && $useSlave && isset($globalShardConfig['schema'][$db]['readers'])) {
				$pool = $globalShardConfig['schema'][$db]['readers'];
			} else {
				$pool = $globalShardConfig['schema'][$db]['proxies'];
			}
			$totalWeights = array_sum($pool);
			$weight       = rand(1, $totalWeights);
			$weightLine   = 0;
			foreach ($pool as $serverName => $serverWeight) {
				$weightLine += $serverWeight;
				if ($weightLine >= $weight) {
					$selectedNodeName = $serverName;
					break;
				}
			}

			//连接失败后，使用默认节点
			try {
				$nodeConfig = $globalShardConfig['nodes'][$selectedNodeName];
				self::$_instance[$instanceName] = self::_newPDO(
					$nodeConfig['host'],
					$nodeConfig['port'],
					$nodeConfig['database_name'],
					$nodeConfig['username'],
					$nodeConfig['password']
				);
			} catch (\PDOException $e) {
				$nodeConfig = $globalShardConfig['nodes'][$defaultNodeName];
				self::$_instance[$instanceName] = self::_newPDO(
					$nodeConfig['host'],
					$nodeConfig['port'],
					$nodeConfig['database_name'],
					$nodeConfig['username'],
					$nodeConfig['password']
				);
			}

			return self::$_instance[$instanceName];
		}

		$hashConfig = $globalShardConfig['schema'][$db]['shard'][$table];
		if (!isset($shardKey[$hashConfig['key']])) {
			throw new ExceptionDB('分表键不存在');
		}

		$mod             = $shardKey[$hashConfig['key']] % $hashConfig['mod'];
		$numAccu         = 0;
		$node_select_idx = null;
		foreach ($hashConfig['locations'] as $idx => $tableNum) {
			$numAccu += $tableNum;
			if ($mod < $numAccu) {
				$node_select_idx = $idx;
				break;
			}
		}
		if ($node_select_idx === null) {
			throw new ExceptionDB('无法映射到表');
		}
		$nodeName = $hashConfig['nodes'][$node_select_idx];
		if (isset(self::$_instance[$nodeName])) {
			return self::$_instance[$nodeName];
		}

		$nodeConfig = $globalShardConfig['nodes'][$nodeName];

		self::$_instance[$nodeName] = self::_newPDO(
			$nodeConfig['host'],
			$nodeConfig['port'],
			$nodeConfig['database_name'],
			$nodeConfig['username'],
			$nodeConfig['password']
		);

		return self::$_instance[$nodeName];
	}

	private static function _newPDO($host, $port, $db, $user, $password)
	{
		try {
			return new \PDO(
				sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', $host, $port, $db),
				$user,
				$password,
				array(
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
					\PDO::ATTR_EMULATE_PREPARES => TRUE,
					\PDO::ATTR_TIMEOUT => 10,
					\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
				)
        );
		} catch (\PDOException $e) {
			$msg = "DBError:[Server:{$host}:{$port}/{$db}][Msg:" . $e->getMessage() . "]";
			self::$_logger->crit($msg);
			throw $e;
		}
	}

	public function beginTransaction()
	{
		if (self::$_transcation === 0) {
			$db = self::getDBConn($this->_table, $this->_dbname, []);
			$db->beginTransaction();
		}
		self::$_transcation++;
	}

	public function commit()
	{
		if (self::$_transcation === 1) {
			$db = self::getDBConn($this->_table, $this->_dbname, []);
			$db->commit();
		}
		self::$_transcation--;
	}

	public function rollback()
	{
		if (self::$_transcation === 1) {
			$db = self::getDBConn($this->_table, $this->_dbname, []);
			$db->rollBack();
		}
		self::$_transcation--;
	}

	public function getFullyTableName()
	{
		return $this->_dbname . '.' . $this->_table;
	}

	public function getDbName()
	{
		return $this->_dbname;
	}

	public function getTable()
	{
		return $this->_table;
	}
}
