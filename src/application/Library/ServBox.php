<?php

namespace App\Library;

use System;

/**
 * Class ServBox
 * @method \App\Service\User User()
 */

class ServBox
{
    protected $_serviceList;

    public function __construct()
    {
        $this->_serviceList = [];
    }

    public function __call($name, $args)
    {
        return $this->get(ucfirst($name), $args);
    }

    public function get($serviceName, $args)
    {
        $argsStr = empty($args) ? '' : implode('_', $args);
        if (isset($this->_serviceList[$serviceName . $argsStr])) {
            return $this->_serviceList[$serviceName . $argsStr];
        }
        $finalServiceName = str_replace('_', '\\', $serviceName);
        $className        = "App\\Service\\{$finalServiceName}";
        $rc               = new \ReflectionClass($className);

        $serviceObj = $rc->newInstanceArgs($args);
        $this->_serviceList[$serviceName . $argsStr] = $serviceObj;
        return $serviceObj;
    }
}


