<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午9:35
 */

namespace App\Models;

use App\Library\Core\DB;

/**
 * This is the model class for table "gw_page_form".
 */
class Form extends DB
{
    public function __construct()
    {
        parent::__construct('gw_page_form', 'admin');
    }
}