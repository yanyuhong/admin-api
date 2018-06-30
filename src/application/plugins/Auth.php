<?php

use App\Library\Error;
use Noodlehaus\Config;

class AuthPlugin extends Yaf_Plugin_Abstract
{
    /** @var  Config */
    protected $_config;

    /**
     * 免登录接口
     * @var array
     */
    public static $noAuth = [
        '/test/getTest' => 1,

        '/user/login' => 1,
        '/user/logout' => 1,
    ];

    public function __construct()
    {
        $this->_config = \Yaf_Registry::get('config');
    }

    /**
     * @param Yaf_Request_Abstract $request
     * @param Yaf_Response_Abstract $response
     */
    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $uri = $request->getRequestUri();
        $controller = strtolower($request->getModuleName() . '/' . $request->getControllerName());
        $callback = $request->get('cb');

        $userId = RegBox()->Session()->getUserId();

        $error = [];

        if (!isset(self::$noAuth[$uri]) && !isset(self::$noAuth[$controller]) && !$userId) {
            $error = Error::$AUTH_ERROR;
            goto END;
        }

        END:
        if ($error) {
            if (!empty($callback)) {
                echo $callback . "(" . json_encode(['code' => (int)$error[0], 'message' => (string)$error[1], 'currentTime' => time(), 'data' => []]) . ");";
                exit();
            } else {
                $response->setHeader('Access-Control-Allow-Origin', '*');
                RegBox()->Response()->sendOutPut([], $error[0], $error[1]);
            }
        }
    }
}