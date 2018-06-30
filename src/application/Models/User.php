<?php

/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午4:09
 */
namespace App\Models;

use App\Library\Core\DB;

/**
 * This is the model class for table "gw_user".
 */
class User extends DB
{
    public function __construct()
    {
        parent::__construct('gw_user', 'admin');
    }
}