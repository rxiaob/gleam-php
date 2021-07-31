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

namespace gleam;

/**
 * 核心类
 */
class App extends Container
{

    /**
     * 框架版本
     * @var string
     */
    public const VERSION = '1.4.0';

    /**
     * 应用配置
     * @var array
     */
    protected $option = [];

    /**
     * 容器绑定
     * @var array
     */
    protected $bind = [
        'app' => App::class,
        'event' => Event::class,
        'request' => Request::class,
        'db' => Db::class,
        'view' => View::class,
    ];

    /**
     * 应用开始时间
     * @var float
     */
    protected $beginTime;

    /**
     * 应用开始内存
     * @var int
     */
    protected $beginMemory;

    /**
     * 构造器
     * @access public
     * @param string $rootPath 应用根目录
     * @param array $option 应用配置
     */
    public function __construct(string $rootPath, array $option = null)
    {
        $this->beginTime = microtime(true);
        $this->beginMemory = memory_get_usage();

        // 根目录
        $this->option['root_path'] = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // 内核目录
        $this->option['core_path'] = __DIR__ . DIRECTORY_SEPARATOR;
        // 应用目录
        $this->option['app_path'] = $this->getRootPath() . 'app' . DIRECTORY_SEPARATOR;
        // 运行时目录
        $this->option['runtime_path'] = $this->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        // 配置文件目录
        $this->option['config_path'] = $this->getRootPath() . 'config' . DIRECTORY_SEPARATOR;

        // 通用环境变量文件
        $this->option['env_file'] = $this->getRootPath() . '.env';
        // 通用函数文件
        $this->option['common_file'] = $this->getAppPath() . 'common.php';
        // 容器配置文件
        $this->option['provider_file'] = $this->getAppPath() . 'provider.php';
        // 事件配置文件
        $this->option['event_file'] = $this->getAppPath() . 'event.php';
        // 服务配置文件
        $this->option['service_file'] = $this->getAppPath() . 'service.php';

        if (!empty($option))
            $this->option = array_merge($this->option, $option);

        if ($provider = $option['provider'] ?? null)
            $this->bind($provider);

        if (is_file($provider_file = $this->option['provider_file']))
            $this->bind(include $provider_file);

        static::setInstance($this);
        $this->instance('app', $this);

    }

    /**
     * 获取根目录
     * @access public
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->option['root_path'];
    }

    /**
     * 获取框架核心目录
     * @access public
     * @return string
     */
    public function getCorePath(): string
    {
        return $this->option['core_path'];
    }

    /**
     * 获取Think框架核心目录，兼容需要
     * @access public
     * @return string
     */
    public function getThinkPath(): string
    {
        return $this->option['core_path'];
    }

    /**
     * 获取当前应用目录
     * @access public
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->option['app_path'];
    }

    /**
     * 获取配置文件目录
     * @access public
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->option['config_path'];
    }

    /**
     * 获取应用运行时目录
     * @access public
     * @return string
     */
    public function getRuntimePath(): string
    {
        return $this->option['runtime_path'];
    }

    /**
     * 环境变量
     * @var array
     */
    public $env = [];

    /**
     * 系统配置
     * @var array
     */
    public $config = [];

    /**
     * 初始化应用
     * @access public
     * @return App
     */
    public function initialize(): App
    {
        // 加载App通用函数
        if (is_file($common_file = $this->option['common_file'])) {
            include_once $common_file;
        }

        // 加载助手函数
        include_once $this->getCorePath() . 'Helper.php';
        include_once $this->getCorePath() . 'HelperExtend.php';

        // 加载环境变量
        $this->env = $this->load_env($this->option['env_file']);

        // 加载配置文件
        $this->config = $this->load_config($this->option['config_path']);

        // 设置时区
        date_default_timezone_set(env('app.default_timezone', 'Asia/Shanghai'));

        // Model初始化
        if (class_exists(\think\Model::class))
            \think\Model::setDb($this->db);

        // 加载事件
        if (is_file($event_file = $this->option['event_file'])) {
            $this->load_event(include $event_file);
        }

        // 加载服务
        if (is_file($service_file = $this->option['service_file'])) {
            $this->load_service(include $service_file);
        }

        event('AppInit');

        return $this;
    }

    /**
     * 加载env配置
     * @access protected
     * @param string $file 文件地址
     * @return array
     */
    protected function load_env(string $file): array
    {
        $env = [];
        if (is_file($file)) {
            $env = parse_ini_file($file, true);
            $env = array_replace_recursive($env, $this->option['env'] ?? []);
            foreach ($env as $key1 => $val1) {
                $name = strtoupper($key1);
                if (is_array($val1)) {
                    foreach ($val1 as $key2 => $val2) {
                        $name2 = $name . '.' . strtoupper($key2);
                        putenv($name2 . '=' . $val2);
                    }
                } else {
                    putenv($name . '=' . $val1);
                }
            }
        }
        return $env;
    }

    /**
     * 加载app配置
     * @access protected
     * @param string $configPath 配置文件目录地址
     * @return array
     */
    protected function load_config(string $configPath): array
    {
        $config = [];
        if (is_dir($configPath)) {
            $files = glob($configPath . '*.php');
            foreach ($files as $file) {
                $config[pathinfo($file, PATHINFO_FILENAME)] = include $file;
            }
        }
        $config = array_replace_recursive($config, $this->option['config'] ?? []);
        $this->config_spread($config, $config);
        return $config;
    }

    private function config_spread(array &$arr, array $node, string $name = ''): void
    {
        foreach ($node as $k => $v) {
            $k = trim($name . '.' . $k, '.');
            if (is_array($v)) {
                $this->config_spread($arr, $v, $k);
            } else {
                $arr[$k] = $v;
            }
        }
    }

    /**
     * 注册应用事件
     * @access protected
     * @param array $event 事件数据
     * @return void
     */
    protected function load_event(array $event): void
    {
        if ($event) {
            if (isset($event['bind'])) {
                $this->event->bind($event['bind']);
            }
            if (isset($event['listen'])) {
                $this->event->listens($event['listen']);
            }
        }
    }

    /**
     * 服务绑定
     * @var array
     */
    protected $services = [];

    /**
     * 注册服务
     * @access protected
     * @param array $services 服务数据
     * @return void
     */
    protected function load_service(array $services): void
    {
        foreach ($services as $service) {
            $service = is_string($service) ? $service : get_class($service);

            if (is_string($service)) {
                $service = new $service($this);
            }

            if (method_exists($service, 'register')) {
                $service->register();
            }

            if (property_exists($service, 'bind')) {
                $this->bind($service->bind);
            }

            $this->services[] = $service;
        }
    }

    /**
     * 启动服务
     * @access protected
     * @return void
     */
    protected function service_boot(): void
    {
        array_walk($this->services, function ($service) {
            if (method_exists($service, 'boot')) {
                return $this->invoke([$service, 'boot']);
            }
        });
    }

    /**
     * 错误处理
     * @access public
     * @param int $level 错误等级
     * @param \Closure $handler 错误处理回调函数
     * @param \Closure $shutdown 中止执行回调函数
     * @return App
     */
    public function error(int $level = E_ALL, \Closure $handler = null, \Closure $shutdown = null): App
    {
        ini_set('display_errors', 'on');
        error_reporting($level);

        set_exception_handler($handler ?? function (\Exception $ex) {
                event('AppException', $ex);
            }
        );

        register_shutdown_function($shutdown ?? function () {
                event('AppShutdown');
            }
        );

        return $this;
    }

    /**
     * http处理
     * @access public
     * @return App
     */
    public function http(): App
    {

        // 启动服务
        $this->service_boot();

        event('HttpRun');

        // 加载控制器
        $this->load_controller();

        event('HttpEnd');

        return $this;
    }

    private $module_name = '';

    /**
     * 获取模块目录名称
     * @access public
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->module_name;
    }

    /**
     * 加载控制器
     * @access protected
     * @param string $path url路径
     * @return bool
     */
    protected function load_controller(string $path = ''): bool
    {
        $this->module_name = '';

        $path = $path ?: uri_path();
        $path = str_replace('..', '', $path);
        $path = preg_replace('/[^a-zA-Z0-9\-\_\/\.]+/', '', $path);
        $path = '/' . trim($path, '/');

        $section = explode('/', $path);

        if (!empty($section[1]) && !empty($section[2])) {
            $class = 'app\\' . strtolower($section[1]) . '\controller\\' . ucfirst($section[2]);
            $method = !empty($section[3]) ? strtolower($section[3]) : 'index';
            if (method_exists($class, $method)) {
                $this->module_name = strtolower($section[1]);
            } else {
                $class = 'app\controller\\' . ucfirst($section[1]);
                $method = strtolower($section[2]);
            }
        } elseif (!empty($section[1])) {
            $class = 'app\controller\\' . ucfirst($section[1]);
            $method = 'index';
        } else {
            $class = 'app\controller\Index';
            $method = 'index';
        }

        if (method_exists($class, $method)) {

            $result = call_user_func([new $class($this), $method]);

            if (is_string($result))
                echo $result;

            if (function_exists('fastcgi_finish_request')) {
                // 提高页面响应
                fastcgi_finish_request();
            }

            return true;
        }

        event('NotFound');

        return false;
    }

    /**
     * 打印执行时间
     * @param string $id 编号
     * @return void
     */
    function elapsed($id = ''): void
    {
        $sec = microtime(true) - $this->beginTime;
        echo trim(sprintf("{$id}.%.6fs<br/>\n", $sec), '.');
    }

    /**
     * 快速启动
     * @access public
     * @param string $rootPath 应用根目录
     * @param array $option 应用配置
     * @param string $engine 框架类型
     * @return void
     */
    public static function run(string $rootPath, array $option = null, string $engine = ''): void
    {
        if (is_file($rootPath)) {
            require $rootPath;
        } elseif ($engine == 'tp') {
            // 使用ThinkPHP框架，需要完成以下步骤
            // 1.composer require topthink/framework
            // 2.config目录导入TP框架配置文件
            // 使用Seesion，需要在全局的中间件定义中开启
            // https://www.kancloud.cn/manual/thinkphp6_0/content
            define('ENGINE_TP', '1');
            if (!class_exists('\think\App'))
                throw new \Exception('未检测到ThinkPHP框架');
            $app = new \think\App($rootPath);
            include_once __DIR__ . DIRECTORY_SEPARATOR . 'HelperExtend.php';
            $http = $app->http;
            $response = $http->run();
            if ($response->getCode() === 404)
                event('NotFound');
            $response->send();
            $http->end($response);
        } else {
            $app = new App($rootPath, $option);
            $app->initialize();
            if (!env('app_debug', false))
                $app->error();
            $app->http();
        }
    }

}
