<?php
ini_set("display_errors", "On");
ini_set('default_charset', 'utf-8');
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('PRC');
define("APP_PATH", realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
include dirname(APP_PATH) . "/vendor/autoload.php";

require APP_PATH . '/application/__init__.php';

$app = new \Yaf_Application(APP_PATH . "/conf/application.ini", 'dev');
$app->bootstrap()->run();
