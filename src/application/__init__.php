<?php

define('NOW', date('Y-m-d H:i:s'));
define('NOW_TIME', time());

/**
 * 获取Service容器
 * @return \App\Library\ServBox
 */
function ServBox()
{
	return Yaf_Registry::get('_serv_box');
}

/**
 * 获取yaf_Register对象容器
 * @return \App\Library\RegBox
 */
function RegBox()
{
	return Yaf_Registry::get('_reg_box');
}

/**
 * 获取Module对象容器
 * @return \App\Library\SysServBox
 */
function SysServBox()
{
	return Yaf_Registry::get('_sys_serv_box');
}

/**
 * 获取当前环境
 * @return Yaf_Config_Abstract
 */
function ENV()
{
	return Yaf_Application::app()->getConfig()->get('env');
}
