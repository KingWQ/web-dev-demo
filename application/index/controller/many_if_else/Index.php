<?php
namespace app\index\controller\many_if_else;

use app\index\logic\ManyIfLogic;

class Index
{
    public function index()
    {
        $params = [
            'task_key'=>'18879276sdswewezds1778sds',
            'alive_day'=>30,
        ];
        $data = (new ManyIfLogic(1,1, $params))->getExportData();
        var_dump($data);
    }
}