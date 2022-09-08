<?php
namespace app\index\controller\monolog;


use redis\PhpRedis;
use think\Controller;

use Monolog\Logger;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\RedisHandler;

use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;


class Index extends Controller
{
    public $log;
    public $html_formatter;
    public $json_formatter;
    public $line_formatter;

    public function index()
    {
        # 1：创建日志服务
        $log = new Logger("test-monolog");
        $this->log = $log;

        # 2. 自定义时区 - 可选, 默认采用 UTC 时间格式
        $log->setTimezone(new \DateTimeZone('Asia/Shanghai'));

        # 3. 自定义时间格式 - 可选
        $dateFormat = "Y-m-d H:i:s";

        # 4. Formatter 部分 (根据功能需求, 选择一个合适的 Formatter)
        # 4.1 将日志信息转化为 HTML 表格, 主要作用于邮件发送或生成日志历史页
        $html_formatter = new HtmlFormatter($dateFormat);
        $this->html_formatter = $html_formatter;

        # 4.2 将日志数据转化为 JSON 格式
        $json_formatter = new JsonFormatter();
        $this->json_formatter = $json_formatter;

        # 4.3 将日志数据转化为一行字符, 可自定义格式
        $output = "%datetime% > %level_name% > %channel% > %message% > %context% > %extra% \n"; # 日志内容格式
        $line_formatter = new LineFormatter($output, $dateFormat);
        $this->line_formatter = $line_formatter;

        # 5. Handler 部分 (根据功能需求, 选择一个合适的 Handler)
        $this->selectHandler('RedisHandler');

        # 6. Processor 部分 (根据功能需求，可选多个 Processor)
        # 6.1 自定义额外数据
        # $log->pushProcessor(function($record){
        #    $record['extra']['age'] = 18;
        #    $record['extra']['sex'] = '男';
        #    return $record;
        # });
        #$log->pushProcessor(new IntrospectionProcessor());
        # $log->pushProcessor(new WebProcessor());
        # $log->pushProcessor(new UidProcessor());
        # $log->pushProcessor(new GitProcessor());
        # $log->pushProcessor(new HostnameProcessor());
        # $log->pushProcessor(new MemoryPeakUsageProcessor());
        # $log->pushProcessor(new MemoryUsageProcessor());
        # $log->pushProcessor(new ProcessIdProcessor());

        # 7. 将记录添加到日志, 根据自身需要, 选择一个日志等级进行记录
        #$log->log("日志等级常量或日志等级数字", "日志消息", "日志内容");
        # $log->log(200, '注册用户:', ['username'=>'Chon', 'height'=>175]);
        $log->debug('Message');
        # $log->info('Message');
        # $log->notice('Message);
        # $log->warning('Message');
        # $log->error('Message);
        # $log->critical('Message');
        # $log->alert('Message');
        # $log->emergency('Message');

        # 8. 保存日志的示例
        # 2022-07-20 15:31:32 > INFO > my_first_log > 注册用户: > {"username":"Chon","height":175} > {"age":18,"sex":"男"}
        # 2022-09-08 15:56:10 > INFO > test-monolog > 注册用户: > {"username":"Chon","height":175} > {"file":"/www/wwwroot/tp5.test/application/index/controller/monolog/Index.php","line":81,"class":"app\\index\\controller\\monolog\\Index","callType":"->","function":"index","age":18,"sex":"男"}

        return 'success';
    }

    /**
     * Handler 部分 (根据功能需求, 选择一个合适的 Handler)
     * 1 将日志信息写信 PHP 错误日志文件中
     * 2 将日志信息通过邮件发送出去
     * 3 将日志写入本地文件
     * 4 将日志写入本地文件, 默认自动按 天 生成的日志文件
     */
    public function selectHandler($type)
    {
        switch($type){
            case 'ErrorLogHandler':
                $error_log_handler = new ErrorLogHandler();
                $error_log_handler->setFormatter($this->line_formatter); # 定义日志内容
                $this->log->pushHandler($error_log_handler); # 入栈
                break;
            case 'NativeMailerHandler':
                $native_mailer_handler = new NativeMailerHandler("收件人邮箱", "邮件主题", "寄件人邮箱");
                $native_mailer_handler->setFormatter($this->html_formatter); # 定义日志内容
                $this->log->pushHandler($native_mailer_handler); # 入栈
                break;
            case 'StreamHandler':
                $stream_handler = new StreamHandler(__DIR__."/log/monolog_log.log"); # 例: __DIR__ . /log/my_first_log.log
                $stream_handler->setFormatter($this->line_formatter); # 定义日志内容
                $this->log->pushHandler($stream_handler); # 入栈
                break;
            case 'RotatingFileHandler':
                $rotating_file_handler = new RotatingFileHandler(__DIR__."/log/monolog_day_log.log"); # 例: __DIR__ . /log/my_first_log.log
                $rotating_file_handler->setFormatter($this->json_formatter); # 定义日志内容
                $this->log->pushHandler($rotating_file_handler); # 入栈
                break;
            case 'RedisHandler':
                $redis = new \Redis();
                $redis->connect(env('redis.host'),env('redis.port'), env('redis.time_out'));
                $redis->auth(env('redis.password'));
                $redis->select(2);
                $redis_handler = new RedisHandler($redis, 'monolog_redis_log');
                $redis_handler->setFormatter($this->line_formatter);
                $this->log->pushHandler($redis_handler);
                break;
        }
    }
}