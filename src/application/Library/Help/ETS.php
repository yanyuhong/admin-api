<?php
namespace App\Library\Help;


/**
 * 执行时间统计
 * Execution time statictis
 */
class ETS
{
    const STAT_ET_DB_CONNECT = 'DB_Connect';
    const STAT_ET_DB_QUERY = 'DB_Query';
    const STAT_ET_MEMCACHE_CONNECT = 'MEMCACHE_Connect';
    const STAT_ET_REDIS = 'REDIS_Query';
    const STAT_ET_MONGO_CONNECT = 'MONGO_Query';
    const STAT_ET_HTTP_QUERY = 'Http_Query';
    const STAT_ET_COMMENT_FILTER = 'cmt_filter';
    const STAT_ET_ES_QUERY = 'Es_Query';
    const STAT_ET_BUILD_INFO = 'Build_Info';
    const STAT_ET_REPORT = 'Report';
    const STAT_ET_DEBUG = 'Debug';
    private static $starts = array();
    private static $warnTimes = array(
        self::STAT_ET_DB_CONNECT       => 0.1,
        self::STAT_ET_DB_QUERY         => 0.1,
        self::STAT_ET_MEMCACHE_CONNECT => 0.05,
        self::STAT_ET_REDIS            => 0.1,
        self::STAT_ET_MONGO_CONNECT    => 0.1,
        self::STAT_ET_HTTP_QUERY       => 1,
        self::STAT_ET_COMMENT_FILTER   => 0.1,
        self::STAT_ET_ES_QUERY         => 0.05,
        self::STAT_ET_BUILD_INFO       => 0.05,
        self::STAT_ET_REPORT           => 0.05,
        self::STAT_ET_DEBUG            => 0.05,
    );
    private static $names = array(
        self::STAT_ET_DB_CONNECT       => 'DB_Connect',
        self::STAT_ET_DB_QUERY         => 'DB_Query',
        self::STAT_ET_MEMCACHE_CONNECT => 'MEMCACHE_Connect',
        self::STAT_ET_REDIS            => 'REDIS_Query',
        self::STAT_ET_MONGO_CONNECT    => 'MONGO_Query',
        self::STAT_ET_HTTP_QUERY       => 'Http_Query',
        self::STAT_ET_COMMENT_FILTER   => 'cmt_filter',
        self::STAT_ET_ES_QUERY         => 'Es_Query',
        self::STAT_ET_BUILD_INFO       => 'Build_Info',
        self::STAT_ET_REPORT           => 'Report',
        self::STAT_ET_DEBUG            => 'Debug',
    );

    private static $_logger;

    public static function start($name)
    {
        self::$starts[$name] = microtime(TRUE);
    }

    public static function setLogger($logger)
    {
        self::$_logger = $logger;
    }

    public static function end($name, $msg = '')
    {
        if (empty(self::$starts[$name])) {
            return FALSE;
        }
        $start = self::$starts[$name];
        $end = microtime(TRUE);
        $executeTime = $end - $start;
        if (isset(self::$warnTimes[$name])) {
            if ($executeTime > self::$warnTimes[$name]) {
                $log = '[ET] ' . self::$names[$name] . ':' . $executeTime . ':' . $msg;
                self::$_logger->warn($log);
            }
        }
        return $executeTime;
    }
}
