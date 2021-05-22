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

class Statistics
{

    /**
     * 服务地址
     * @var string
     */
    const ADDRESS = 'udp://127.0.0.1:44444';

    /**
     * 包头长度
     * @var integer
     */
    const PACKAGE_FIXED_LENGTH = 17;

    /**
     * udp包最大长度
     * @var integer
     */
    const MAX_UDP_PACKGE_SIZE = 65507;

    /**
     * char类型能保存的最大数值
     * @var integer
     */
    const MAX_CHAR_VALUE = 255;

    /**
     * 编码
     * @param string $module 模块名称
     * @param string $method 方法名称
     * @param float $elapsed 执行时间
     * @param bool $success 是否成功
     * @param int $status 状态码
     * @param string $msg 消息
     * @return string
     */
    public static function encode($module, $method, $elapsed, $success, $status = 0, $msg = '')
    {
        // 防止模块名过长
        if (strlen($module) > self::MAX_CHAR_VALUE)
            $module = substr($module, 0, self::MAX_CHAR_VALUE);

        // 防止接口名过长
        if (strlen($method) > self::MAX_CHAR_VALUE)
            $method = substr($method, 0, self::MAX_CHAR_VALUE);

        // 防止消息过长
        $module_len = strlen($module);
        $method_len = strlen($method);
        $size = self::MAX_UDP_PACKGE_SIZE - self::PACKAGE_FIXED_LENGTH - $module_len - $method_len;
        if (strlen($msg) > $size)
            $msg = substr($msg, 0, $size);

        // 打包
        return pack('CCfCNnN', $module_len, $method_len, $elapsed, $success ? 1 : 0, $status, strlen($msg), time()) . $module . $method . $msg;
    }

    /**
     * 解码
     * @param string $data 已编码数据
     * @return array
     */
    public static function decode($data)
    {
        // 解包
        $head = unpack("Cmodule_len/Cmethod_len/felapsed/Csuccess/Nstatus/nmsg_len/Ntime", $data);
        $module = substr($data, self::PACKAGE_FIXED_LENGTH, $head['module_len']);
        $method = substr($data, self::PACKAGE_FIXED_LENGTH + $head['module_len'], $head['method_len']);
        $msg = substr($data, self::PACKAGE_FIXED_LENGTH + $head['module_len'] + $head['method_len']);
        return [
            'module' => $module,
            'method' => $method,
            'elapsed' => $head['elapsed'],
            'success' => (bool)$head['success'],
            'time' => $head['time'],
            'status' => $head['status'],
            'msg' => $msg,
        ];
    }

    /**
     * 发送数据
     * @param string $address 地址
     * @param string $data 数据
     * @return bool
     */
    public static function send($address, $data)
    {
        $socket = stream_socket_client($address);
        if (!$socket)
            return false;
        return stream_socket_sendto($socket, $data) == strlen($data);
    }

    protected static $timeMap = [];

    /**
     * 统计记时
     * @param string $module 模块名称
     * @param string $method 方法名称
     * @return float
     */
    public static function tick($module = '', $method = '')
    {
        return self::$timeMap[$module][$method] = microtime(true);
    }

    /**
     * 统计报告
     * @param string $module 模块名称
     * @param string $method 方法名称
     * @param bool $success 是否成功
     * @param int $status 状态码
     * @param mixed $msg 消息
     * @return void
     */
    public static function report($module, $method, $success, $status, $msg)
    {
        if (isset(self::$timeMap[$module][$method]) && self::$timeMap[$module][$method] > 0) {
            $start_time = self::$timeMap[$module][$method];
            self::$timeMap[$module][$method] = 0;
        } else if (isset(self::$timeMap['']['']) && self::$timeMap[''][''] > 0) {
            $start_time = self::$timeMap[''][''];
            self::$timeMap[''][''] = 0;
        } else {
            $start_time = microtime(true);
        }
        $elapsed = microtime(true) - $start_time;
        $data = self::encode($module, $method, $elapsed, $success, $status, $msg);
        self::send(self::ADDRESS, $data);
    }

    /**
     * 模拟数据测试
     * @param int $num 发送次数
     * @return void
     */
    public static function test($num = 1)
    {
        while ($num--) {
            usleep(mt_rand(10000, 600000));
            $success = mt_rand(0, 1);
            $status = 0;
            $msg = 'ok!';
            $data = [
                'user' => ['name', 'age', 'gender', 'birthday'],
                'company' => ['country', 'city', 'district', 'address'],
                'news' => ['category', 'title', 'summary', 'content']
            ];
            $module = array_rand($data);
            $method = $data[$module][array_rand($data[$module])];
            if (!$success) {
                try {
                    switch (mt_rand(0, 1)) {
                        case 1:
                            call_user_func('left');
                            break;
                        default:
                            $method();
                            break;
                    };
                } catch (\Throwable $e) {
                    $status = $e->getCode();
                    $msg = $e->getMessage();
                }
            }
            self::report($module, $method, $success, $status, $msg);
        }
    }

}