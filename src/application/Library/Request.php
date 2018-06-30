<?php

namespace App\Library;

use App\Library\Help\Arr;

/**
 * api请求类
 * Class Request
 */
class Request extends \Yaf_Request_Abstract
{
    /**
     * token
     * @var string
     */
    public $token;

    /**
     * Yaf  Request对象
     * @var \Yaf_Request_Http
     */
    public $YafRequest;

    /**
     * 头信息
     */
    public $header;

    public function __construct(\Yaf_Request_Http $YafRequest)
    {
        $this->YafRequest = $YafRequest;
        $this->token = Arr::get($this->getHeader(), 'token');
        if (!$this->token) {
            $this->token = $YafRequest->get('token');
        }
    }

    public function getHeader()
    {
        if (!$this->header) {
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) === 'HTTP_') {
                    $this->header[strtolower(str_replace('_', '-', substr($key, 5)))] = urldecode($value);
                }
            }
        }
        return $this->header;
    }

    public function getYafRequest()
    {
        return $this->YafRequest;
    }

    public function getToken()
    {
        return $this->token;
    }
}
