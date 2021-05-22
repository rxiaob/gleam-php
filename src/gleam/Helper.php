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

/**
 * 通用助手函数
 */

use gleam\App;
use gleam\Container;
use gleam\facade\Request;
use gleam\facade\View;

if (!function_exists('app')) {
    /**
     * 快速获取容器中的实例 支持依赖注入
     * @param string $name 类名或标识 默认获取当前应用实例
     * @param array $args 参数
     * @param bool $newInstance 是否每次创建新的实例
     * @return object|App
     */
    function app(string $name = '', array $args = [], bool $newInstance = false)
    {
        return Container::getInstance()->make($name ?: App::class, $args, $newInstance);
    }
}

if (!function_exists('bind')) {
    /**
     * 绑定一个类到容器
     * @param string|array $abstract 类标识、接口（支持批量绑定）
     * @param mixed $concrete 要绑定的类、闭包或者实例
     * @return Container
     */
    function bind($abstract, $concrete = null)
    {
        return Container::getInstance()->bind($abstract, $concrete);
    }
}

if (!function_exists('invoke')) {
    /**
     * 调用反射实例化对象或者执行方法 支持依赖注入
     * @param mixed $call 类名或者callable
     * @param array $args 参数
     * @return mixed
     */
    function invoke($call, array $args = [])
    {
        if (is_callable($call)) {
            return Container::getInstance()->invoke($call, $args);
        }

        return Container::getInstance()->invokeClass($call, $args);
    }
}

if (!function_exists('env')) {
    /**
     * 获取环境变量值
     * @access public
     * @param string $name 环境变量名
     * @param string $default 默认值
     * @return mixed
     */
    function env(string $name = '', $default = null)
    {
        if (empty($name))
            return app()->env ?? [];

        $value = getenv($name);

        if ($value === false)
            $value = $_ENV[$name] ?? false;

        if ($value === false)
            return $default;

        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'empty':
                return '';
            case 'null':
                return null;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param string $name 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    function config(string $name = '', $default = null)
    {
        $config = app()->config ?? [];

        if (empty($name))
            return $config;

        if (0 === strpos($name, '?'))
            return array_key_exists(substr($name, 1), $config);

        return $config[$name] ?? $default;
    }
}

if (!function_exists('event')) {
    /**
     * 触发事件
     * @param mixed $event 事件名（或者类名）
     * @param mixed $args 参数
     * @return mixed
     */
    function event($event, $args = null)
    {
        return app()->event->trigger($event, $args);
    }
}

if (!function_exists('cookie_set')) {
    /**
     * 设置Cookie
     * @access public
     * @param string $name 名称
     * @param string $value 值
     * @param mixed $option 参数
     * @return void
     */
    function cookie_set(string $name, string $value, $option = null): void
    {
        $option = array_merge([
            // cookie 保存时间
            'expire' => 0,
            // cookie 保存路径
            'path' => '/',
            // cookie 有效域名
            'domain' => '',
            //  cookie 启用安全传输
            'secure' => false,
            // httponly设置
            'httponly' => false,
            // samesite 设置，支持 'strict' 'lax'
            'samesite' => '',
        ], config('cookie') ?? [], $option ?? []);
        $expire = $option['expire'];
        $path = $option['path'];
        $domain = $option['domain'];
        $secure = $option['secure'];
        $httponly = $option['httponly'];
        $samesite = $option['samesite'];
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}

if (!function_exists('cookie')) {
    /**
     * 操作Cookie
     * @param string $name 名称
     * @param mixed $value 值
     * @param mixed $option 参数
     * @return mixed
     */
    function cookie(string $name = '', $value = '', $option = null)
    {
        if (empty($name))
            return $_COOKIE;

        if (is_null($value)) {
            // 删除
            $option['expire'] = time() - 3600;
            cookie_set($name, '', $option);
        } elseif ('' === $value) {
            // 获取
            return 0 === strpos($name, '?') ? array_key_exists(substr($name, 1), $_COOKIE) : @$_COOKIE[$name];
        } else {
            // 设置
            if (!is_null($option)) {
                if (is_numeric($option) || $option instanceof DateTimeInterface) {
                    $option = ['expire' => $option];
                }
            }
            cookie_set($name, $value, $option);
        }
    }
}

if (!function_exists('session')) {
    /**
     * 操作Session
     * @param string $name 名称
     * @param mixed $value 值
     * @return mixed
     */
    function session($name = '', $value = '')
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            session_start();

        if (is_null($name)) {
            // 清空
            $_SESSION = [];
        } elseif ($name === '') {
            // 获取全部
            return $_SESSION;
        } elseif (is_null($value)) {
            // 删除
            unset($_SESSION[$name]);
        } elseif ('' === $value) {
            // 获取
            return 0 === strpos($name, '?') ? array_key_exists(substr($name, 1), $_SESSION) : $_SESSION[$name] ?? null;
        } else {
            // 设置
            $_SESSION[$name] = $value;
        }
    }
}

if (!function_exists('cache')) {
    /**
     * 缓存管理
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param int $expire 过期时间
     * @return mixed
     */
    function cache(string $name, $value = '', int $expire = 0)
    {
        $dir = app()->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR;
        $file = $dir . md5($name) . '.php';
        if (is_null($value)) {
            // 删除
            if (is_file($file))
                unlink($file);
            return true;
        } elseif ('' === $value) {
            // 获取
            if (is_file($file) && ($code = @file_get_contents($file)) !== false) {
                $time = (int)substr($code, 8, 12);
                if ($time > 0 && $time < time()) {
                    unlink($file);
                    return null;
                }
                $code = substr($code, 21);
                return eval($code);
            }
            return null;
        } elseif ($value instanceof \Closure && $expire > 0) {
            // 不存在或过期重加载，必须设置过期时间
            if (is_file($file) && ($code = @file_get_contents($file)) !== false) {
                $time = (int)substr($code, 8, 12);
                if ($time >= time()) {
                    $code = substr($code, 21);
                    return eval($code);
                }
            }
            $code = $value();
            cache($name, $code, $expire);
            return $code;
        } else {
            // 设置
            if (!is_dir($dir))
                mkdir($dir, 0755, true);
            if ($expire > 0)
                $expire = time() + $expire;
            $s = "<?php\n//" . sprintf('%012d', $expire) . "\nreturn ";
            if ($value instanceof \Closure)
                $value = $value();
            if (is_array($value))
                $s .= "json_decode('" . json_encode($value) . "', true)";
            elseif (is_numeric($value))
                $s .= $value;
            else
                $s .= "'" . str_replace('\'', '\\\'', $value) . "'";
            $s .= ";";
            $result = file_put_contents($file, $s);
            if ($result) {
                clearstatcache();
                return true;
            }
            return false;
        }
    }
}

if (!function_exists('input')) {
    /**
     * 获取输入数据 支持默认值和过滤
     * @param string $key 获取的变量名
     * @param mixed $default 默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    function input(string $key = '', $default = null, $filter = '')
    {
        if (0 === strpos($key, '?')) {
            $key = substr($key, 1);
            $has = true;
        }

        if ($pos = strpos($key, '.')) {
            // 指定参数来源
            $method = substr($key, 0, $pos);
            //            ,      , 'header'
            if (in_array($method, ['get', 'post', 'put', 'param', 'cookie', 'request', 'server', 'header', 'file'])) {
                $key = substr($key, $pos + 1);
                if ('server' == $method && is_null($default)) {
                    $default = '';
                }
            } else {
                $method = 'param';
            }
        } else {
            // 默认为自动判断
            $method = 'param';
        }

        return isset($has) ?
            request()->has($key, $method) :
            request()->$method($key, $default, $filter);
    }
}

if (!function_exists('json')) {
    /**
     * 输出Json
     * @param mixed $data 返回的数据
     * @param int $status 状态码
     * @param array $header 头部
     * @return string
     */
    function json($data = [], $status = 200, $header = []): string
    {
        head(array_merge($header, [
            'Content-Type' => 'application/json; charset=utf-8'
        ]), $status);
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('view')) {
    /**
     * 渲染模板输出
     * @param string $template 模板文件
     * @param array $vars 模板变量
     * @param int $status 状态码
     * @param callable $filter 内容过滤
     * @return string
     */
    function view(string $template = '', $vars = [], $status = 200, $filter = null): string
    {
        head([
            'Content-Type' => 'text/html; charset=utf-8'
        ], $status);
        if (!class_exists('\think\Template')) {
            extract($vars, EXTR_OVERWRITE);
            $file = app_path('view') . trim($template, '/') . '.html';
            include $file;
            return '';
        }
        return View::getFetch($template, $vars, $filter);
    }
}

if (!function_exists('display')) {
    /**
     * 渲染内容输出
     * @param string $content 渲染内容
     * @param array $vars 模板变量
     * @param int $status 状态码
     * @param callable $filter 内容过滤
     * @return string
     */
    function display(string $content, $vars = [], $status = 200, $filter = null): string
    {
        head([
            'Content-Type' => 'text/html; charset=utf-8'
        ], $status);
        return app('view')->getDisplay($content, $vars, $filter);
    }
}

if (!function_exists('download')) {
    /**
     * 下载输出
     * @param string $filename 要下载的文件
     * @param string $name 显示文件名
     * @param bool $content 是否为内容
     * @param int $expire 有效期（秒）
     * @return string
     */
    function download(string $filename, string $name = '', bool $content = false, int $expire = 0): string
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if ($content) {
            $mimeType = 'text/html; charset=utf-8';
            $size = strlen($filename);
            if (!$name)
                $name = date('YmdHis') . '.txt';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filename);
            $size = filesize($filename);
            if (!$name)
                $name = basename($filename);
        }

        if ($name)
            $name = iconv("utf-8", "GBK", $name);

        head([
            'Pragma' => $expire > 0 ? 'public' : 'no-cache',
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'Cache-control' => $expire > 0 ? 'max-age=' . $expire : 'no-cache, must-revalidate',
            'Content-Disposition' => 'attachment; filename=' . $name,
            'Content-Length' => $size,
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => gmdate("D, d M Y H:i:s", time() + $expire) . ' GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s', time()) . ' GMT'
        ]);

        return $content ? $filename : file_get_contents($filename);
    }
}

if (!function_exists('token')) {
    /**
     * 获取Token令牌
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function token(string $name = '__token__', string $type = 'md5'): string
    {
        return Request::buildToken($name, $type);;
    }
}

if (!function_exists('token_field')) {
    /**
     * 生成令牌隐藏表单
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function token_field(string $name = '__token__', string $type = 'md5'): string
    {
        $token = token($name, $type);
        return '<input type="hidden" name="' . $name . '" value="' . $token . '" />';
    }
}

if (!function_exists('token_meta')) {
    /**
     * 生成令牌meta
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function token_meta(string $name = '__token__', string $type = 'md5'): string
    {
        $token = token($name, $type);
        return '<meta name="csrf-token" content="' . $token . '">';
    }
}

if (!function_exists('request')) {
    /**
     * 获取当前Request对象实例
     * @return Request
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('root_path')) {
    /**
     * 获取根目录
     * @param string $path
     * @return string
     */
    function root_path($path = '')
    {
        return app()->getRootPath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('app_path')) {
    /**
     * 获取应用目录
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->getAppPath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('config_path')) {
    /**
     * 获取配置目录
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->getConfigPath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('runtime_path')) {
    /**
     * 获取运行时目录
     * @param string $path
     * @return string
     */
    function runtime_path($path = '')
    {
        return app()->getRuntimePath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('dump')) {
    /**
     * 浏览器友好的变量输出
     * @param mixed $vars 要输出的变量
     * @return void
     */
    function dump(...$vars)
    {
        ob_start();
        var_dump(...$vars);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, ENT_SUBSTITUTE);
            }
            $output = '<pre>' . $output . '</pre>';
        }

        echo $output;
    }
}

if (!function_exists('halt')) {
    /**
     * 调试变量并且中断输出
     * @param mixed $vars 调试变量或者信息
     */
    function halt(...$vars)
    {
        var_dump(...$vars);
        exit();
    }
}

if (!function_exists('trace')) {
    /**
     * 日志记录
     * @param mixed $vars 内容
     * @return bool
     */
    function trace(...$vars): bool
    {
        if (!$vars)
            return false;
        $path = runtime_path('log');
        $vars_count = count($vars);
        if ($vars_count == 1) {
            $file = $path . date('YmdH') . '.txt';
            $s = '[' . date('Y-m-d H:i:s') . '] ' . print_r($vars[0], true) . "\n";
            $append = true;
        } else {
            $file = $path . trim($vars[0], '+');
            $s = '[' . date('Y-m-d H:i:s') . ']';
            if ($vars_count > 2) {
                $s .= "\n";
                for ($i = 1; $i < $vars_count; $i++) {
                    $s .= $i . '.' . print_r($vars[$i], true) . "\n";
                }
                $s .= "\n";
            } else {
                $s .= ' ' . print_r($vars[1], true) . "\n";
            }
            $append = strpos($vars[0], '+') === 0;
        }
        $p = dirname($file);
        if (!is_dir($p)) {
            mkdir($p, 0755, true);
        }
        if ($append) {
            return file_put_contents($file, $s, FILE_APPEND) !== false;
        } else {
            return file_put_contents($file, $s) !== false;
        }
    }
}

