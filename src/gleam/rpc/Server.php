<?php
/**
 * Gleam-PHP
 *
 * An open source mini development framework for PHP
 *
 * Copyright (c) 2021 by rxiaob (https://github.com/rxiaob/gleam-php)
 *
 * @author     rxiaob <rxiaob@qq.com>
 * @copyright  rxiaob <rxiaob@qq.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @link       https://github.com/rxiaob/gleam-php
 */
declare (strict_types=1);

namespace gleam\rpc;

use gleam\App;
use gleam\Db;
use gleam\Event;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class Server extends App
{
    /**
     * 容器绑定
     * @var array
     */
    protected $bind = [
        'app' => App::class,
        'event' => Event::class,
        'db' => Db::class,
    ];

    /**
     * 构造器
     * @access public
     * @param string $rootPath 应用根目录
     * @param array $options 应用配置
     */
    public function __construct(string $rootPath, array $options = null)
    {
        parent::__construct($rootPath, $options);
        $this->initialize();
        $this->service_boot();
    }

    /**
     * 处理web请求
     * @param Request $request 请求
     * @param string $webroot 根目录
     * @param callable $intercept 拦截器
     * @param string $index 默认页
     * @return mixed
     */
    public static function web(Request $request, string $webroot, callable $intercept = null, $index = '/index.html')
    {
        $path = $request->path();

        // 拦截处理
        if ($intercept && ($ret = $intercept($path)))
            return $ret;

        // 默认页
        if ($path === '/')
            $path = $index;

        // 请求文件
        $webroot = realpath($webroot);
        $file = realpath($webroot . $path);

        // 检查文件
        if ($file === false)
            return new Response(404, [], '404 Not Found');

        // 路径安全检查
        if (strpos($file, $webroot) !== 0)
            return new Response(400);

        // 返回PHP页面
        if (strtolower(\pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
            \ob_start();
            try {
                include $file;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            return \ob_get_clean();
        }

        // 检查文件最后修改时间
        if ($if_modified_since = $request->header('if-modified-since')) {
            $info = \stat($file);
            $modified_time = $info ? \date('D, d M Y H:i:s', $info['mtime']) . ' ' . \date_default_timezone_get() : '';
            if ($modified_time === $if_modified_since) {
                return new Response(304);
            }
        }

        // 返回文件
        return (new Response())->withFile($file);
    }

    /**
     * 监听服务
     * @param string $name 服务名称
     * @param int $process 进程数量
     * @param string $address 监听地址
     * @param callable $accept 应答
     * @param callable $start 启动回调
     * @param callable $error 错误回调
     * @return void
     */
    public static function listener($name, $process, $address, $accept, $start = null, $error = null)
    {

        // 开启的端口
        $worker = new Worker($address);

        // 设置服务进程，windows系统不支持
        $worker->count = $process;

        // 设置服务名称
        $worker->name = $name;

        if ($error)
            $worker->onError = $error;

        if ($start)
            $worker->onWorkerStart = $start;

        // 接受数据回调函数
        $worker->onMessage = $accept;

        // 如果不是在根目录启动，则运行
        if (!defined('ROOT_START')) {
            Worker::runAll();
        }

    }

    /**
     * @param string $rule
     * @param callable $callback
     * @return mixed
     */
    public static function timer($rule, $callback)
    {
        new \Workerman\Crontab\Crontab($rule, $callback);
    }

    /**
     * 开启全部服务
     * @param string $rootPath 应用根目录
     * @param array $options 应用配置
     * @param string $directory 服务目录
     * @return void
     */
    public static function start(string $rootPath, array $options = null, string $directory = '*')
    {

        $app = new static($rootPath, $options);

        // 标记是根目录启动
        define('ROOT_START', 1);

        // 加载所有服务
        foreach (glob($rootPath . '/app/' . $directory . '/start*.php') as $file) {
            require $file;
        }

        // 运行所有服务
        Worker::runAll();

    }

}