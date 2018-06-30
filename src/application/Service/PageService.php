<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午7:08
 */

namespace App\Service;


use App\Library\Core\BaseService;
use App\Models\Page;

class PageService extends BaseService
{
    private $_page;

    public function __construct()
    {
        parent::__construct();
        $this->_page = new Page();
    }

    public function getHomeList()
    {
        $pages = $this->_page->select(
            [
                'parent_id' => 0
            ],
            [
                'select' => 'id,title,`group`',
                'order_by' => '`group` asc'
            ]
        );
        return $pages;
    }
}