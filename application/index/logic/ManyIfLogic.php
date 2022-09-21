<?php
namespace app\index\logic;


class ManyIfLogic
{
    private $platform;  //平台类型
    private $type;      //操作类型
    private $params;    //请求参数

    public function __construct($platform,$type,$params)
    {
        $this->platform = $platform;
        $this->type = $type;
        $this->params = $params;
    }

    public function getExportData()
    {
        $funcName = $this->getFuncName($this->platform,$this->type);
        return call_user_func([$this,$funcName],$this->params);
    }

    public function getFuncName($platform,$type)
    {
        switch($platform){
            case 1:$platform   = "telegram";break;
            case 2:$platform   = "facebook";break;
        }
        switch($type){
            case 0: $opt = "opened";break;        //筛开通
            case 1: $opt = "alive";break;         //筛活跃
            case 2: $opt = "gender";break;        //筛性别年龄
            case 3: $opt = "disabled";break;      //筛封号
        }
        return $platform.ucfirst($opt);
    }

    public function telegramOpened($params)
    {
        $taskKey    = $params['task_key'];
        $task       = FilterTask::where('task_key',$taskKey)->find();

        $data       = [];
        $handle     = @fopen($task->result_url, 'r');
        if (!$handle) {
            throw new \Exception('导出结果数据异常，请联系管理处理');
        }

        while(!feof($handle)) {
            $tmp = explode(':', trim(fgets($handle)));
            if( !is_array($tmp) ) continue;
            $data[] = trim($tmp[0]);
        }
        fclose($handle);
        return $data;
    }

    public function telegramAlive($params)
    {
        $aliveDay       = in_array($params['alive_day'],[1,3,7,10,15,30]) ? $params['alive_day'] : 0;
        $aliveDayTime   = $aliveDay*24*3600;
        $taskKey        = $params['task_key'];

        $task           = FilterTask::where('task_key',$taskKey)->find();
        $taskGmtTime    =strtotime($task->gmt_create);
        $data           = [];

        $handle         = @fopen($task->result_url, 'r');
        if (!$handle) {
            throw new \Exception('导出结果数据异常，请联系管理处理');
        }


        while(!feof($handle)) {
            $tmp = explode(':', trim(fgets($handle)));
            if( !is_array($tmp) ) continue;
            $tmpTime = explode('_', $tmp[1]);
            if(!is_array($tmpTime)) continue;

            if($aliveDay == 0 ){
                $data[] = trim($tmp[0]);
            }
            $aliveTime = substr($tmpTime[0],0,10);
            if($aliveDay > 0 && $aliveTime >= ($taskGmtTime-$aliveDayTime)){
                $data[] = trim($tmp[0]);
            }
        }
        fclose($handle);

        return $data;
    }




}