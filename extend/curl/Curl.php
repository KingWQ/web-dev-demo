<?php
namespace curl;

class Curl {

    /**
     * @param        $url
     * @param string $post
     * @param string $cookie
     * @param int    $timeout
     * @param int    $returnCookie
     *
     * @return bool|string
     */
    protected static function curl_request($url,$post='',$cookie='',$timeout=10,$returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");

        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$post);
        }

        if($cookie) curl_setopt($curl, CURLOPT_COOKIE, $cookie);

        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }

        return $data;
    }
    public static function post($url, $data = [], $cookie='', $timeout=10, $returnCookie=0) {
        return self::curl_request($url,$data,$cookie,$timeout,$returnCookie);
    }

    public static function get($url, $data = [],$timeout=10,$cookie='', $returnCookie=0) {
        if($data) {
            $url .= "?" . http_build_query($data);
        }
        return self::curl_request($url,"",$cookie,$timeout, $returnCookie);

    }

    /**
     * curl支持 检测
     * @return bool|false|resource|null
     */
    protected function check_curl() {
        $ch = null;
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init();
        if (!is_resource($ch)) {
            return false;
        }
        return $ch;
    }



    /**
     * @param $nodes
    [
    [
    'url'=>'http://192.168.0.120:8010/api/user/get_info',
    'data'=>['uid'=>1,'user_login'=>'admin']
    ],
    [
    'url'=>'http://127.0.0.1:8010/api/user/get_info',
    'data'=>['uid'=>1,'user_login'=>'1']
    ],
    ];
     *
     * @return array
    [
    'http://192.168.0.120:8010/api/user/get_info'=>'{"status":"1","data":{"user_id":"1","user_login":"admin","user_name":"系统管理员"}',
    'http://127.0.0.1:8010/api/user/get_info'=>'{"status":"1","data":{"user_id":"1","user_login":"1","user_name":"1"}',
    ]
     */
    static function multiple_threads_get($nodes=[]){
        $urls = [];
        foreach($nodes as $url){
            if(is_array($url['data'])){
                $urls[] = $url['url'].'?'.http_build_query($url['data']);
            }else{
                $urls[] = $url['url'];
            }
        }
        $mh = curl_multi_init();
        $curl_array = [];
        foreach($urls as $i => $url){
            $curl_array[$i] = curl_init($url);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, 2);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = null;
        do{
            curl_multi_exec($mh, $running);
        }while($running > 0);
        $res = [];
        foreach($urls as $i => $url){
            $res[$url] = curl_multi_getcontent($curl_array[$i]);
        }
        foreach($urls as $i => $url){
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);

        return $res;
    }

    /**
     * @param $nodes
     *
     * @return array
     */
    static function multiple_threads_post($nodes=[]){
        $mh = curl_multi_init();
        $curl_array = array();
        foreach($nodes as $i => $item){
            $curl_array[$i] = curl_init($item['url']);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_array[$i], CURLOPT_POST, 1);
            curl_setopt($curl_array[$i], CURLOPT_POSTFIELDS, $item['data']);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = null;
        do{
            curl_multi_exec($mh, $running);
        }while($running > 0);
        $res = array();
        foreach($nodes as $i => $item){
            $res[$item['url']] = curl_multi_getcontent($curl_array[$i]);
        }
        foreach($nodes as $i => $item){
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);

        return $res;
    }


    /**
     * php完美实现下载远程图片保存到本地
     *
     * @param string $url 文件url
     * @param string $save_dir 保存文件目录
     * @param string $filename 保存文件名称
     * @param array $exts 文件后缀: 如 ['.gif','.jpg']
     * @param int $type 使用的下载方式
     * @return array
     */
    public static function  get_file_to_local($url='', $save_dir='', $filename='', $exts=[], $type=0){
        if(trim($url)===''){
            return array('file_name'=>'','save_path'=>'','error'=>1);
        }
        if(trim($save_dir)===''){
            $save_dir='./';
        }
        if(trim($filename)===''){//保存文件名
            $ext=strrchr($url,'.');
            if (!empty($ext) && !in_array($ext, $exts, true)){
                return array('file_name'=>'','save_path'=>'','error'=>2);
            }

            $filename=time().$ext;
        }
        if(0!==strrpos($save_dir,'/')){
            $save_dir.='/';
        }
        //创建保存目录
        if(!file_exists($save_dir) && !mkdir($save_dir, 0777, true) && !is_dir($save_dir)){
            return array('file_name'=>'','save_path'=>'','error'=>3);
        }
        //获取远程文件所采用的方法
        if($type){
            $ch=curl_init();
            $timeout=5;
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
            $img=curl_exec($ch);
            curl_close($ch);
        }else{
            ob_start();
            readfile($url);
            $img=ob_get_clean();
        }
        $size=strlen($img);
        if ($size===0){
            return array('file_name'=>'','save_path'=>'','error'=>4);

        }
        //文件大小
        $fp2=@fopen($save_dir.$filename, 'ab');
        fwrite($fp2,$img);
        fclose($fp2);
        unset($img);
        return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
    }

}