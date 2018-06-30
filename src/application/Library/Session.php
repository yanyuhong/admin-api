<?php

namespace App\Library;

use App\Library\Help\Arr;

/**
 * Session对象
 * Class Session
 */
class Session
{
    /**
     * @var Session
     */
    protected static $instance;

    private $_user;
    private $_userId;

    private static $_token;

    private function __construct()
    {
    }

    /**
     * 由Bootstrap注入
     * @param $token
     */
    public static function setToken($token)
    {
        self::$_token = $token;
    }

    /**
     * @return Session
     */
    public static function getInstance()
    {
        // Create a new instance
        if (self::$instance == null) {
            self::$instance = new self();
        }

        self::$instance->_parseToken();
        return self::$instance;
    }

    private function _parseToken()
    {
        $user_id = 0;
        $user = [];

        $user_session = ServBox()->User()->getUserByToken(self::$_token);
        if($user_session){
            $user_id = $user_session['id'];
            $user = $user_session;
        }

        $this->_userId = $user_id;
        $this->_user = $user;

        return $this->_userId;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getUserId()
    {
        return $this->_userId;
    }
}
