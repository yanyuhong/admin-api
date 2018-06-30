<?php

namespace App\Library\Core;

use App\Library\Error;
use App\Library\Help\Uri;
use Monolog\Logger;
use Noodlehaus\Config;
use App\Library\RegBox;

/**
 * Class BaseController
 *
 * @package App\Library\Core
 * @method \Yaf_Request_Http getRequest()
 * @method \Yaf_Response_Http getResponse()
 */
class BaseController extends \Yaf_Controller_Abstract
{
    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \App\Library\ServBox
     */
    protected $container;
    protected $post;
    protected $get;
    protected $request;

    public function init()
    {
        $this->log = RegBox()->Log();
        $this->config = \Yaf_Registry::get('config');
        $this->container = ServBox();

        $this->post = $this->getRequest()->getPost();
        $this->get = $this->getRequest()->getQuery();
        $this->request = $this->getRequest()->getRequest();
    }

    /**
     * 成功的响应输出
     *
     * @param array $data
     */
    protected function sendSuccess($data = [])
    {
        $this->sendResult(Error::$SUCCESS, $data);
    }

    /**
     * 响应输出
     *
     * @param array $code
     * @param array $data
     */
    protected function sendResult($code, $data = [])
    {
        if (!isset($code[2])) {
            RegBox()->Response()->sendOutPut($data, $code[0], $code[1]);
        } else {
            RegBox()->Response()->sendOutPut($data, $code[0], $code[1], $code[2]);
        }
    }

    protected function outputJson($data)
    {
        RegBox()->Response()->simpleOutJson($data);
    }

    protected function flushResult($code, $data = [])
    {
        if (!isset($code[2])) {
            return RegBox()->Response()->flushOutPut($data, $code[0], $code[1]);
        } else {
            return RegBox()->Response()->flushOutPut($data, $code[0], $code[1], $code[2]);
        }
    }

    /**
     * 成功的响应输出
     *
     * @param array $data
     *
     * @return bool
     */
    protected function flushSuccess($data = [])
    {
        return $this->flushResult(Error::$SUCCESS, $data);
    }

}
