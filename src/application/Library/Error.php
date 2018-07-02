<?php

namespace App\Library;

class Error
{
    protected $container;
    static $SUCCESS = [0, "成功"];

    static $AUTH_ERROR = [-126, "权限错误"];
    static $SIGN_ERROR = [-147, "签名错误"];
    static $LOGIN_ERROR = [-100, "用户名或密码错误"];
    static $PAGE_ERROR = [-121, "页面不存在"];
    static $ADMIN_PERM_ERROR = [-148, "暂无此权限"];


    public function __construct($container)
    {
        $this->container = $container;
    }
}
