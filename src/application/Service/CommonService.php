<?php
/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午9:01
 */

namespace App\Service;


use App\Library\Core\BaseService;

class CommonService extends BaseService
{

    public function getOption($mothed)
    {
        if (method_exists(self::class, $mothed)) {
            return call_user_func([self::class, $mothed]);
        } else {
            return [];
        }
    }

    private function userStatus()
    {
        return [
            [
                'value' => '1',
                'label' => '正常',
            ],
            [
                'value' => '2',
                'label' => '冻结',
            ]
        ];
    }
}