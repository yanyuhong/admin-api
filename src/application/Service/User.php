<?php

/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午3:25
 */
namespace App\Service;

use App\Library\Core\BaseService;

class User extends BaseService
{
    private $_user;

    public function __construct()
    {
        parent::__construct();
        $this->_user = new \App\Models\User();
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
        $passwordMd5 = \App\Models\User::setPassword($username, $password);
        if ($passwordMd5 != $user['password']) {
            return false;
        }
        $token = \App\Models\User::setToken($username);
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