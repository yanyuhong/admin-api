<?php

/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午3:25
 */
namespace App\Service;

use App\Library\Core\BaseService;
use App\Models\User;

class UserService extends BaseService
{
    private $_user;

    public function __construct()
    {
        parent::__construct();
        $this->_user = new User();
    }

    public function getUserByToken($token)
    {
        $user = $this->_user->selectOne([
            'token' => $token,
        ]);
        return $user;
    }

    public function login($username, $password)
    {
        $user = $this->_user->selectOne([
            'username' => $username
        ]);
        if (!$user) {
            return false;
        }
        $passwordMd5 = User::setPassword($username, $password);
        if ($passwordMd5 != $user['password']) {
            return false;
        }
        $token = User::setToken($username);
        $this->_user->update(
            [
                'id' => $user['id'],
            ],
            [
                'token' => $token,
            ]
        );

        return $token;
    }

    public function logout($id)
    {
        $this->_user->update(
            [
                'id' => $id,
            ],
            [
                'token' => ''
            ]
        );
    }
}