<?php

namespace App\Library;

use Monolog\Logger;
use Noodlehaus\Config;

/**
 * 获取Yaf_Register注册过的对象
 * Class YafRegister
 *
 * @method Request Request()
 * @method Response Response()
 * @method Session Session()
 * @method Logger Log()
 * @method Config Config()
 */
class RegBox
{
    public function __call($name, $args = [])
    {
	    $obj = \Yaf_Registry::get('_' . strtolower($name));
	    return $obj;
    }
}


