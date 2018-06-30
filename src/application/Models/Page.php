<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午7:06
 */

namespace App\Models;


use App\Library\Core\DB;

/**
 * This is the model class for table "gw_page".
 */
class Page extends DB
{

    public function __construct()
    {
        parent::__construct('gw_page', 'admin');
    }
}