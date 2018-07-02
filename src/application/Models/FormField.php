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
 * This is the model class for table "gw_page_form_field".
 */
class FormField extends DB
{
    const TYPE_TEXT = 1;
    const TYPE_SELECT = 2;
    const TYPE_SELECT_MORE = 3;
    const TYPE_DATE = 4;
    const TYPE_FILE = 5;

    public function __construct()
    {
        parent::__construct('gw_page_form_field', 'admin');
    }
}