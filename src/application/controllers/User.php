<?php

/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午5:28
 */

use App\Library\Core\BaseController;
use App\Library\Help\Arr;
use App\Library\Error;

class UserController extends BaseController
{

    public function loginAction()
    {
        $username = Arr::get($this->post, 'username');
        $password = Arr::get($this->post, 'password');

        $token = ServBox()->UserService()->login($username, $password);

        if (!$token) {
            $this->sendResult(Error::$LOGIN_ERROR);
        }

        $this->sendSuccess([
            'token' => $token,
        ]);
    }

    public function logoutAction()
    {
        $userId = RegBox()->Session()->getUserId();
        ServBox()->UserService()->logout($userId);
        $this->sendSuccess();
    }

    public function getInfoAction()
    {
        $user = RegBox()->Session()->getUser();
        $data = [
            'username' => (string)$user['username'],
            'name' => (string)$user['name'],
            'mobile' => (string)$user['mobile'],
            'avatar' => (string)$user['avatar'],
        ];
        $this->sendSuccess([
            'user' => $data,
        ]);
    }
}