<?php

/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午6:55
 */
use App\Library\Core\BaseController;
use App\Library\Help\Arr;

class HomeController extends BaseController
{

    public function pageListAction()
    {
        $pages = ServBox()->PageService()->getHomeList();
        $data = [];
        foreach ($pages as $page) {
            if (!isset($data[$page['group']])) {
                $data[$page['group']] = [
                    'title' => $page['group'],
                    'pages' => [],
                ];
            }
            $data[$page['group']]['pages'][] = [
                'id' => $page['id'],
                'title' => $page['title']
            ];
        }

        $this->sendSuccess([
            'groups' => array_values($data)
        ]);
    }
}