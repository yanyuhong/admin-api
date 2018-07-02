<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午8:34
 */

namespace App\Models;

use App\Library\Core\DB;

/**
 * This is the model class for table "gw_page_filter".
 */
class Filter extends DB
{
    const TYPE_TEXT = 1;
    const TYPE_SELECT = 2;
    const TYPE_DATE = 3;
    const TYPE_DATE_RANGE = 4;

    public function __construct()
    {
        parent::__construct('gw_page_filter', 'admin');
    }
}