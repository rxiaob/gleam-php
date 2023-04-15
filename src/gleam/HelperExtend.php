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

if (!function_exists('head')) {
    /**
     * 输出
     * @param array $header 头部信息
     * @param int $status 状态码
     * @return bool
     */
    function head($header = [], $status = 200): bool
    {
        if (!headers_sent()) {
            // 发送状态码
            http_response_code($status);
            // 发送头部信息
            if ($header) {
                foreach ($header as $name => $val) {
                    header($name . (!is_null($val) ? ':' . $val : ''));
                }
            }
            return true;
        }
        return false;
    }
}

if (!function_exists('write')) {
    /**
     * 输出
     * @param mixed $content 内容
     * @param array $header 头部信息
     * @param int $status 状态码
     * @param string $contentType 内容类型
     * @param string $charset 字符集
     * @return void
     */
    function write($content, $header = null, $status = 200, $contentType = 'text/html', $charset = 'utf-8')
    {
        head(array_merge($header ?? [], [
            'Content-Type' => $contentType . '; charset=' . $charset
        ]), $status);
        if (is_string($content))
            echo $content;
        else
            echo $content->getContent();
        if (function_exists('fastcgi_finish_request')) {
            // 提高页面响应
            fastcgi_finish_request();
        }
        exit();
    }
}

if (!function_exists('go')) {
    /**
     * 页面跳转
     * @param string $url 重定向地址
     * @param int $status 状态码
     * @return void
     */
    function go(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Cache-control:no-cache,must-revalidate');
        header('Location:' . $url);
        exit;
    }
}

if (!function_exists('db')) {
    /**
     * 获取数据
     * @param string $name 数据表名
     * @return mixed
     */
    function db($name = '')
    {
        if (defined('ENGINE_TP')) {
            if (empty($name))
                return \think\facade\Db::class;
            return \think\facade\Db::name($name);
        } else {
            if (empty($name))
                return \gleam\facade\Db::class;
            return \gleam\facade\Db::name($name);
        }
    }
}

if (!function_exists('uri_path')) {
    /**
     * 返回Uri的路径部分。
     * @return string
     */
    function uri_path()
    {
        $uri = $_SERVER['REQUEST_URI'] . '?';
        return '/' . trim(strstr($uri, '?', true), '/');
    }
}

if (!function_exists('view_file')) {
    /**
     * 返回视图文件。
     * @param string $root 根目录
     * @param string $path 路径
     * @return string
     */
    function view_file($root, $path)
    {
        $path = str_replace('..', '', $path);
        $path = preg_replace('/[^a-zA-Z0-9\-\_\/\.]+/', '', $path);
        if ($path === '/')
            $path = '/index';
        $file = $root . $path . '.html';
        $file = realpath($file);
        return $file;
    }
}

if (!function_exists('upload')) {
    /**
     * 上传文件
     * @param array $file 文件信息
     * @param string $name 保存文件名
     * @param string $directory 保存路径
     * @param string $exts 允许上传的文件类型
     * @param int $size 允许上传的文件大小
     * @return string
     * @throws Exception
     */
    function upload(array $file, string $name = null, string $directory = null, $exts = null, $size = 0): string
    {
        if (empty($file))
            throw new Exception('未发现上传文件!');
        if ($file['error'] !== 0) {
            throw new Exception($file['error']);
        }
        $file_name = trim($file['name'] ?? '', '.');
        if (empty($file_name))
            throw new Exception('文件名不存在!');
        if (strpos($file_name, '.') === false)
            throw new Exception('文件类型不存在!');
        $file_ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
        if (empty($exts))
            $exts = env('upfile.exts', 'jpg,jpeg,png,gif');
        if (!in_array($file_ext, explode(',', $exts))) {
            throw new Exception('文件类型不正确!');
        }
        $file_size = (int)$file['size'];
        if ($size <= 0)
            $size = (int)env('upfile.size', 1024 * 1024 * 2);
        if ($file_size <= 0)
            throw new Exception('文件大小不正确!');
        if ($file_size > $size)
            throw new Exception('文件大小超过限制!');
        $root = root_path() . env('upfile.path', 'public/uploads');
        $directory = $directory ?: 'file';
        $p = '/' . trim($directory, '/') . '/' . date('Ymd') . '/';
        if (!is_dir($root . $p)) {
            mkdir($root . $p, 0755, true);
        }
        if (!$name) {
            list($msec, $sec) = explode(' ', microtime());
            $name = $sec . substr($msec, 2, 4) . '.' . $file_ext;
        }
        $f = $root . $p . $name;
        if (!rename($file['tmp_name'], $f))
            throw new Exception('上传失败!');
        $f = '/uploads' . $p . $name;
        return $f;
    }
}

if (!function_exists('err')) {
    /**
     * 抛出错误
     * @param string $message 错误提示
     * @param int $code 错误码
     * @return void
     */
    function err($message = '', $code = 0): void
    {
        throw new Exception($message, $code);
    }
}

if (!function_exists('def')) {
    /**
     * 获取默认值
     * @param mixed $val
     * @param mixed $def
     * @return mixed
     */
    function def($value, $default)
    {
        if (is_string($value)) {
            return isset($value) && $value != '' ? $value : $default;
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return empty($value) ? $default : $value;
        }
    }
}

if (!function_exists('substr_w')) {
    /**
     * 获取字符串片段
     * @param string $str
     * @param string $left
     * @param string $right
     * @return string
     */
    function substr_w($str, $left, $right)
    {
        $a = strpos($str, $left);
        if ($a !== false) {
            $b = strpos($str, $right, $a);
            if ($b !== false) {
                return substr($str, $a + 1, $b - $a - 1);
            }
        }
        return '';
    }
}

if (!function_exists('encrypt')) {
    /**
     * des加密
     * @param string $str 待加密字符串
     * @param string $key 密钥
     * @param string $algorithm 算法
     * @return string
     * @throws Exception
     */
    function encrypt($str, $key = '', $algorithm = 'DES-ECB'): string
    {
        if (empty($str))
            return '';
        if (empty($key))
            $key = env('app_key');
        if (empty($key))
            throw new Exception('未设置密钥!');
        $result = openssl_encrypt($str, $algorithm, $key, 0);
        if ($result === false)
            throw new Exception('加密失败!');
        return $result;
    }
}

if (!function_exists('decrypt')) {
    /**
     * des解密
     * @param string $str 待解密字符串
     * @param string $key 密钥
     * @param string $algorithm 算法
     * @return string
     * @throws Exception
     */
    function decrypt($str, $key = '', $algorithm = 'DES-ECB'): string
    {
        if (empty($str))
            return '';
        if (empty($key))
            $key = env('app_key');
        if (empty($key))
            throw new Exception('未设置密钥!');
        $result = openssl_decrypt($str, $algorithm, $key, 0);
        if ($result === false)
            throw new Exception('解密失败!');
        return $result;
    }
}

if (!function_exists('view_path_filter')) {
    /**
     * 模板路径过滤
     * @param string $path 模板路径
     * @return string
     */
    function view_path_filter($path): string
    {
        return $path;
    }
}

if (!function_exists('controller_hanger')) {
    /**
     * 控制器钩子
     * @param string $path 地址路径
     * @param array $section 地址解析
     * @return array
     */
    function controller_hanger($path, $section): array
    {
        $method = '';

        if (!empty($section[1]) && !empty($section[2])) {
            $class = 'app\\' . strtolower($section[1]) . '\controller\\' . ucfirst($section[2]);
            $method = !empty($section[3]) ? strtolower($section[3]) : 'index';
            if (method_exists($class, $method)) {
                $module = strtolower($section[1]);
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

        return [$module, $class, $method];
    }
}

if (!function_exists('dd')) {
    /**
     * KINT调试
     * @param array ...$vars
     * @codeCoverageIgnore
     */
    function dd(...$vars)
    {
        Kint::$aliases[] = 'dd';
        Kint::dump(...$vars);
        exit;
    }
}
