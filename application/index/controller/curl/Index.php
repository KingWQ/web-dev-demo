<?php
namespace app\index\controller\curl;

use curl\Curl;

class Index
{
    //测试普通请求(可发送文件)
    public function index()
    {
        $res = Curl::get("http://127.0.0.1:9001/test.php",['name'=>'1']);
        $res = Curl::post("http://127.0.0.1:9001/test.php",['age'=>'123','name'=>new \CURLFile("/path/to/1.png")]);
    }

    //测试并行发送(可发送文件)
    public function index1()
    {
        $nodes=[
            [
                'url'=>'http://192.168.0.120:8010/api/user/get_info',
                'data'=>[
                    'uid'=>1,
                    'app_id'=>'system',
                    'authen'=>'a90fc09aac9bc7af435f3d0de231421e6dcee8c6b1e24b23172e43376c11a991',
                    'dept_id'=>'1',
                    'user_login'=>'admin',
                    'file'=>new CURLFile("/path/1.png"),
                ]
            ],
            [
                'url'=>'http://127.0.0.1:8010/api/user/get_info',
                'data'=>['uid'=>1,'app_id'=>'system','authen'=>'a90fc09aac9bc7af435f3d0de231421e6dcee8c6b1e24b23172e43376c11a991','dept_id'=>'1','user_login'=>'1']
            ],
        ];

        $res = Curl::multiple_threads_get($nodes);
    }

    //远程下载文件
    public function index2()
    {

        $res = Curl::get_file_to_local("http://mmbiz.qpic.cn/mmbiz_jpg/tnoJFTuLBa99ic78GjkCnFAVia2jTa71gnJxMKhC6XcnKC0zuS5mv4nicuzjUiaLFmcv6sARbbJM2qDwTEw6PWVU3w/640?wx_fmt=jpeg",
            'testimg','123.jpg',['.jpg'],1);

        var_dump($res);
    }
}