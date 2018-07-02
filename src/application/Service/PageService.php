<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午7:08
 */

namespace App\Service;


use App\Library\Core\BaseService;
use App\Models\Filter;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Header;
use App\Models\Operate;
use App\Models\Page;

class PageService extends BaseService
{
    private $_page;
    private $_filter;
    private $_form;
    private $_formField;
    private $_operate;
    private $_header;

    public function __construct()
    {
        parent::__construct();
        $this->_page = new Page();
        $this->_filter = new Filter();
        $this->_form = new Form();
        $this->_formField = new FormField();
        $this->_operate = new Operate();
        $this->_header = new Header();
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

    public function getPage($id)
    {
        $page = $this->_page->selectOne([
            'id' => $id,
        ]);
        return $page;
    }

    public function getFilters($page_id)
    {
        $filters = $this->_filter->select(
            [
                'page_id' => $page_id
            ],
            [
                'order_by' => 'rank asc,id asc'
            ]
        );
        return $filters;
    }

    public function getForms($page_id)
    {
        $forms = $this->_form->select(
            [
                'page_id' => $page_id
            ]
        );
        return $forms;
    }

    public function getFormFields($form_id)
    {
        $fields = $this->_formField->select(
            [
                'form_id' => $form_id,
            ],
            [
                'order_by' => 'rank asc,id asc',
            ]
        );
        return $fields;
    }

    public function getOperates($page_id)
    {
        $operates = $this->_operate->select(
            [
                'page_id' => $page_id,
            ],
            [
                'order_by' => 'rank asc,id asc'
            ]
        );
        return $operates;
    }

    public function getHeaders($page_id)
    {
        $headers = $this->_header->select(
            [
                'page_id' => $page_id,
            ],
            [
                'order_by' => 'rank asc,id asc'
            ]
        );
        return $headers;
    }
}