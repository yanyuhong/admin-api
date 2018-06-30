<?php
namespace App\Library\Core;

use Monolog\Logger;
use Noodlehaus\Config;

class BaseService
{
    /** @var  \App\Library\ServBox */
    protected $_container;
    /** @var  Config */
    protected $_config;
    /** @var  Logger */
    protected $_log;

    public function __construct()
    {
        $this->_container = ServBox();
        $this->_config = \Yaf_Registry::get('config');
        $this->_log = \Yaf_Registry::get('_log');
    }
}
