<?php

namespace App\Library\Core;

use App\Library\Help\ETS;
use App\Library\RegBox;
use \Exception;

class Redis
{
    private $_reconn = false;
    private $_lastConnInfo = null;
    private $_lastNodeName = null;
    private $_lastDbIdx = null;
    private static $_instance;
    private static $_globalConfig;
    private $_config;
    private $_connList;
    public static $timeout = 1;
    private static $_readCmd = [
        'get' => 1,
        'hget' => 1,
        'hmget' => 1,
        'hgetall' => 1,
        'zrange' => 1,
        'zrevrange' => 1,
        'zrevrangebyscore' => 1,
    ];

    public function __construct($dbname, $config)
    {
        $this->_dbname = $dbname;
        $this->_config = $config;
        $this->_connList = [];
    }

    public function getDbName()
    {
        return $this->_dbname;
    }

    public static function setGlobalConfig($config)
    {
        self::$_globalConfig = $config;
    }

    /**
     * @param $db
     *
     * @return \Redis|bool
     */
    static public function getInstance($db)
    {
        if ( !isset(self::$_globalConfig) || self::$_globalConfig === null) {
            return false;
        }
        if ( !isset(self::$_instance[$db]) || is_null(self::$_instance[$db])) {
            self::$_instance[$db] = new self($db, self::$_globalConfig['schema'][$db]);
        }
        return self::$_instance[$db];
    }

    public function __call($name, $args)
    {
        $redisCmd = $name;
        if (isset($this->_config['shard_method'])) {
            $key = $args[0];
            $_method = $this->_config['shard_method'];
            $conn = $this->$_method($redisCmd, $key);
        } else {
            $nodeName = $this->_config['nodes'][0];
            $dbIdx = $this->_config['db'];
            $this->_lastNodeName = $nodeName;
            $this->_lastDbIdx = $dbIdx;
            if (isset($this->_connList[$nodeName][$dbIdx])) {
                $conn = $this->_connList[$nodeName][$dbIdx];
            } else {
                $nodeConfig = self::$_globalConfig['nodes'][$nodeName];
                $persist = isset($this->_config['persist']) && $this->_config['persist'] ? true : false;
                $conn = $this->getConn($nodeConfig['host'], $nodeConfig['port'], $nodeConfig['passwd'], $dbIdx, self::$timeout, $persist);
                $this->_connList[$nodeName][$dbIdx] = $conn;
            }
        }
        ETS::start(ETS::STAT_ET_REDIS);
        $_args = $args;
//        if ($redisCmd === 'scan' || $redisCmd === 'hscan' || $redisCmd ===
//            'sscan' || $redisCmd === 'zscan'
//        ) {
//            $_args = array();
//            foreach ($args as $key => $value) {
//                $_args[$key] = &$args[$key];
//            }
//        }

        try {
            $result = call_user_func_array([$conn, $redisCmd], $_args);
            $this->_errCheck($conn);//redis使用优化，操作命令失败抛异常
        } catch (\Exception $e) {
            $tmpKeyData = json_encode(['key' => empty($args[0]) ? '' : $args[0]]);
            $msg = "RedisError:[node:{$nodeName}][dbindex:{$dbIdx}][cmd:{$redisCmd}][key:{$tmpKeyData}][Msg:" . $e->getMessage() . "]";
            RegBox()->Log()->critical($msg);
            RegBox()->Log()->info($e->getTraceAsString());
            //throw $e;

            //retry
            if ($this->_reconn && isset($this->_lastNodeName) && isset($this->_lastDbIdx) && !empty($this->_lastConnInfo)) {
                $conn = $this->getConn($this->_lastConnInfo['host'], $this->_lastConnInfo['port'], $this->_lastConnInfo['passwd'],
                    $this->_lastConnInfo['dbIdx'], $this->_lastConnInfo['timeout'], $this->_lastConnInfo['persist']);
                $this->_connList[$nodeName][$dbIdx] = $conn;
                $result = call_user_func_array([$conn, $redisCmd], $_args);
            }
        }

        ETS::end(ETS::STAT_ET_REDIS, $name . '=>' . json_encode($args));
        return $result;
    }

    private function _errCheck($conn)
    {
        /** @var \Redis $conn */
        $err = $conn->getLastError();
        if (null != $err) {
            $conn->clearLastError();
            throw new \Exception($err, 100000);
        }
    }

    public function getConn($host, $port, $passwd, $dbIdx, $timeout, $persist)
    {
        $this->_lastConnInfo = ['host' => $host, 'port' => $port, 'passwd' => $passwd, 'dbIdx' => $dbIdx, 'timeout' => $timeout, 'persist' => $persist];
        $log = RegBox()->Log();
        try {
            $conn = new \Redis();
            $i = 3;
            do {
                if ($persist) {
                    $RS = $conn->pconnect($host, $port, $timeout);
                } else {
                    $RS = $conn->connect($host, $port, $timeout);
                }
                if ( !$RS) {
                    $msg = "RedisError:[Server:{$host}:{$port}][Msg:connect failed]";
                    $log->error($msg);
                }
                if ($passwd) {
                    try {
                        $RS = $conn->auth($passwd);
                    } catch (\Exception $e) {
                        $msg = "AuthRedisError:[Server:{$host}:{$port}][Msg:" . $e->getMessage() . "]";
                        $log->error($msg);
                        $RS = false;
                    }
                }
                if ( !$RS) {
                    $msg = "RedisError:[Server:{$host}:{$port}][Msg:auth failed]";
                    $log->error($msg);
                }
                if (true === $RS) {
                    break;
                }
                if (1 == $i) {
                    throw new \Exception('retry fail');
                }
            } while (--$i);
            if ($dbIdx) {
                $conn->select($dbIdx);
            }
        } catch (\Exception $e) {
            $msg = "RedisError:[Server:{$host}:{$port}][Msg:" . $e->getMessage() . "]";
            $log->critical($msg);
            throw $e;
        }
        return $conn;
    }

    private function _shardContentCache($redisCmd, $key)
    {
        $keyMD5 = md5($key);
        $nodeIdx = hexdec(substr($keyMD5, 0, 2)) % $this->_config['node_num'];
        $nodeName = $this->_config['nodes'][$nodeIdx];
        $dbIdx = hexdec(substr($keyMD5, -2)) % $this->_config['db_num'];
        $this->_lastNodeName = $nodeName;
        $this->_lastDbIdx = $dbIdx;
        if (isset($this->_connList[$nodeName][$dbIdx])) {
            return $this->_connList[$nodeName][$dbIdx];
        }
        $nodeConfig = self::$_globalConfig['nodes'][$nodeName];
        $conn = $this->getConn($nodeConfig['host'], $nodeConfig['port'], $nodeConfig['passwd'], $dbIdx, self::$timeout, false);
        $this->_connList[$nodeName][$dbIdx] = $conn;
        return $conn;
    }

    /**
     * 无用，暂时保留
     * @return \Redis
     */
    private function _randomProxy()
    {
        $nodeIdx = array_rand($this->_config['nodes']);
        $nodeName = $this->_config['nodes'][$nodeIdx];
        if (isset($this->_connList[$nodeName][0])) {
            return $this->_connList[$nodeName][0];
        }
        $nodeConfig = self::$_globalConfig['nodes'][$nodeName];
        $conn = new \Redis();
        //连接不成功就依次尝试
        if ( !$conn->connect($nodeConfig['host'], $nodeConfig['port'], self::$timeout)) {
            foreach ($this->_config['nodes'] as $nodeName) {
                if (isset($this->_connList[$nodeName][0])) {
                    return $this->_connList[$nodeName][0];
                }
                $nodeConfig = self::$_globalConfig['nodes'][$nodeName];
                if ($conn->connect($nodeConfig['host'], $nodeConfig['port'], self::$timeout)) {
                    break;
                }
            }
        }
        $this->_connList[$nodeName][0] = $conn;
        return $conn;
    }

    private function _readOnSlave($redisCmd)
    {
        $redisCmd = strtolower($redisCmd);
        $dbIdx = $this->_config['db'];
        if (isset(self::$_readCmd[$redisCmd])) {
            $nodeIdx = array_rand($this->_config['read_pool']);
            $nodeName = $this->_config['read_pool'][$nodeIdx];
        } else {
            $nodeName = $this->_config['nodes'][0];
        }
        $this->_lastNodeName = $nodeName;
        $this->_lastDbIdx = $dbIdx;
        if (isset($this->_connList[$nodeName][$dbIdx])) {
            return $this->_connList[$nodeName][$dbIdx];
        }
        $nodeConfig = self::$_globalConfig['nodes'][$nodeName];
        $conn = $this->getConn($nodeConfig['host'], $nodeConfig['port'], $nodeConfig['passwd'], $dbIdx, self::$timeout, false);
        $this->_connList[$nodeName][$dbIdx] = $conn;
        return $conn;
    }

    /**
     * @desc 设置命令执行的时候是否在失败情况下自动重新建立新连接试一次[应用层自行选择,必须是可重试幂等的命令]
     * @param bool $isReconn
     */
    public function setReconn($isReconn = false)
    {
        $this->_reconn = $isReconn;
    }

    /*
     * 返回redis 链接,主要解决scan等引用返回问题
     */
    public function getRealConnect()
    {
        $nodeName            = $this->_config['nodes'][0];
        $dbIdx               = $this->_config['db'];
        $this->_lastNodeName = $nodeName;
        $this->_lastDbIdx    = $dbIdx;
        if (isset($this->_connList[$nodeName][$dbIdx])) {
            $conn = $this->_connList[$nodeName][$dbIdx];
        } else {
            $nodeConfig                         = self::$_globalConfig['nodes'][$nodeName];
            $persist                            = isset($this->_config['persist']) && $this->_config['persist'] ? true : false;
            $conn                               = $this->getConn(
                $nodeConfig['host'],
                $nodeConfig['port'],
                $nodeConfig['passwd'],
                $dbIdx,
                self::$timeout,
                $persist
            );
            $this->_connList[$nodeName][$dbIdx] = $conn;
        }
        return $conn;
    }


}
