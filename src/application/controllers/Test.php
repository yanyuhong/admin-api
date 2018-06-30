<?php

use App\Library\Core\BaseController;

class TestController extends BaseController {
    /**
     * @apiGroup AP test
     * @apiVersion 0.0.1
     * @api {get} /getTest 测试接口
     **/
    public function getTestAction() {
        $this->sendSuccess();
    }
}