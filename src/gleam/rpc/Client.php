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

namespace gleam\rpc;

class Client
{

    /**
     * 异步调用发送数据前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';

    /**
     * 异步调用接收数据
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';

    /**
     * 服务端地址
     * @var array
     */
    protected static $address = [];

    /**
     * 设置/获取服务端地址
     * @param string|array $address
     * @return array
     */
    public static function config($address)
    {
        if (!empty($address))
            self::$address = is_array($address) ?: explode(',', $address);
        return self::$address;
    }

    /**
     * 实例的服务名
     * @var string
     */
    protected $service_name = '';

    /**
     * 构造函数
     * @param string $service_name
     */
    protected function __construct($service_name)
    {
        $this->service_name = $service_name;
    }

    /**
     * 同步调用实例
     * @var string
     */
    protected static $instances = [];

    /**
     * 异步调用实例
     * @var string
     */
    protected static $asyncInstances = [];

    /**
     * 获取一个实例
     * @param string $service_name
     * @return mixed
     */
    public static function instance($service_name)
    {
        if (!isset(self::$instances[$service_name]))
            self::$instances[$service_name] = new self($service_name);
        return self::$instances[$service_name];
    }

    /**
     * 调用
     * @param string $method
     * @param array $args
     * @param int $mode 模式，同步发送接收|异步发送|异步接受
     * @param int $timeout 超时时间，单位秒
     * @return mixed
     * @throws \Exception
     */
    public function invoke($method, $args, $mode = 0, $timeout = 5)
    {
        // 判断是否是异步发送
        if ($mode === 1) {
            $instance_key = $method . serialize($args);
            if (isset(self::$asyncInstances[$instance_key])) {
                throw new \Exception($this->service_name . "->asend_$method(" . implode(',', $args) . ") have already been called");
            }
            self::$asyncInstances[$instance_key] = new self($this->service_name);
            return self::$asyncInstances[$instance_key]->send($method, $args);
        }
        // 如果是异步接受数据
        if ($mode === 2) {
            $instance_key = $method . serialize($args);
            if (!isset(self::$asyncInstances[$instance_key])) {
                throw new \Exception($this->service_name . "->arecv_$method(" . implode(',', $args) . ") have not been called");
            }
            $tmp = self::$asyncInstances[$instance_key];
            unset(self::$asyncInstances[$instance_key]);
            return $tmp->recv();
        }
        // 同步发送接收
        $this->send($method, $args, $timeout);
        return $this->recv();
    }

    /**
     * 调用
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        $mode = 0;
        // 判断是否是异步发送
        if (0 === strpos($method, self::ASYNC_SEND_PREFIX)) {
            $method = substr($method, strlen(self::ASYNC_SEND_PREFIX));
            $mode = 1;
        }
        // 如果是异步接受数据
        if (0 === strpos($method, self::ASYNC_RECV_PREFIX)) {
            $method = substr($method, strlen(self::ASYNC_RECV_PREFIX));
            $mode = 2;
        }
        return $this->invoke($method, $args, $mode);
    }

    /**
     * 服务端的socket连接
     * @var resource
     */
    protected $connection = null;

    /**
     * 打开服务端连接
     * @param int $timeout 超时时间，单位秒
     * @return void
     * @throws \Exception
     */
    protected function open($timeout = 5)
    {
        $address = self::$address[array_rand(self::$address)];
        $this->connection = stream_socket_client($address, $err_no, $err_msg);
        if (!$this->connection)
            throw new \Exception("can not connect to $address , $err_no:$err_msg");
        stream_set_blocking($this->connection, true);
        stream_set_timeout($this->connection, $timeout);
    }

    /**
     * 关闭服务端连接
     * @return void
     */
    protected function close()
    {
        fclose($this->connection);
        $this->connection = null;
    }

    /**
     * 发送数据给服务端
     * @param string $method
     * @param array $args
     * @param int $timeout 超时时间，单位秒
     * @return bool
     * @throws \Exception
     */
    public function send($method, $args, $timeout = 5)
    {
        $this->open($timeout);
        $data = self::encode([
            'class' => $this->service_name,
            'method' => $method,
            'params' => $args,
        ]);
        if (fwrite($this->connection, $data) !== strlen($data))
            throw new \Exception('Can not send data');
        return true;
    }

    /**
     * 从服务端接收数据
     * @return mixed
     * @throws \Exception
     */
    public function recv()
    {
        $ret = fgets($this->connection);
        $this->close();
        if (!$ret)
            throw new \Exception("recv data empty");
        $data = self::decode($ret);
        if (empty($data))
            throw new \Exception("recv data empty");
        if ($data['status'] === 400)
            throw new \Exception($data['msg']);
        return $data['data'];
    }

    /**
     * 打包，当向客户端发送数据的时候会自动调用
     * @param mixed $buffer
     * @return string
     */
    public static function encode($buffer)
    {
        // json序列化，并加上换行符作为请求结束的标记
        return json_encode($buffer) . "\n";
    }

    /**
     * 解包，当接收到的数据字节数等于input返回的值（大于0的值）自动调用
     * 并传递给onMessage回调函数的$data参数
     * @param string $buffer
     * @return mixed
     */
    public static function decode($buffer)
    {
        // 去掉换行，还原成数组
        return json_decode(trim($buffer), true);
    }

}