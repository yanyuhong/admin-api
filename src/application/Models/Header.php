<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/7/2
 * Time: 下午3:39
 */

namespace App\Models;

use App\Library\Core\DB;

/**
 * This is the model class for table "gw_page_header".
 */
class Header extends DB
{

    public function __construct()
    {
        parent::__construct('gw_page_header', 'admin');
    }
}