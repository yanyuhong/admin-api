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
        $user = $this->_user->selectOne(
            [
                'token' => $token,
            ]
        );
        return $user;
    }
}