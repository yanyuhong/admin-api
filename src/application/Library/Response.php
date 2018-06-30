<?php

namespace App\Library;


/**
 * 响应类
 * Class Request
 */
class Response
{
	/**
	 * @var \Yaf_Response_Http
	 */
	private  $YafResponse;
	public function __construct($response)
	{
		$this->YafResponse = $response;
	}

	/**
	 * 标准响应输出
	 *
	 * @param array $data
	 * @param       $errCode
	 * @param       $errMsg
	 */
	public function sendOutPut($data = [], $errCode, $errMsg)
	{
		$cb = RegBox()->Request()->YafRequest->getRequest('cb');
		$out = ['code' => $errCode, 'message' =>$errMsg, 'currentTime' => time(), 'data' => $data ? : new \stdClass()];
		if ($cb) {
			$this->YafResponse->setHeader('Content-Type', 'text/javascript; charset=utf-8');
			$this->YafResponse->setBody($cb . '(' .json_encode($out, JSON_UNESCAPED_UNICODE) . ')');
		} else {
			$this->YafResponse->setHeader('Content-Type', 'application/json; charset=utf-8');
			$this->YafResponse->setBody(json_encode($out, JSON_UNESCAPED_UNICODE));
		}
		$this->YafResponse->response();
		$this->YafResponse->clearBody();

		if ($errCode < 0) {
			RegBox()->Log()->info("[RESULT_ERROR] code : {$errCode}, msg : {$errMsg}, uri : " . RegBox()->Request()->YafRequest->getRequestUri(),
				['request' => $_REQUEST, 'head' => RegBox()->Request()->getHeader()]
			);
		}
		exit();
	}
}
