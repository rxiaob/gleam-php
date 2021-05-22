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

abstract class Model
{

    public static function __callStatic($name, $args)
    {
        // 找不到方法抛出异常
        if (!method_exists(static::class, $name)) {
            throw new \Exception('method ' . $name . ' not found', 404);
        }

        // 如果设置了RPC地址从RPC服务器获取数据
        if (!defined('SERVER_RPC')) {
            $rpc_address = @constant(static::class . '::RPC_ADDRESS') ?? getenv('rpc.' . static::class);
            $rpc_address = $rpc_address ?: getenv('rpc.address');
            if ($rpc_address) {
                Client::config($rpc_address);
                $client = Client::instance(static::class);
                return $client->invoke($name, $args);
            }
        }

        // 正常调用，方法必须设置为 protected static
        return call_user_func_array([static::class, $name], $args);
    }

    /**
     * 查询数据集
     * @param string $name 表名
     * @param mixed $where 查询条件
     * @param mixed $order 查询排序
     * @param mixed $field 查询字段
     * @return array
     */
    protected static function select($name, $where = null, $order = null, $field = '*')
    {
        return db($name)->field($field)->where($where ?? [])->order($order)->select();
    }

    /**
     * 分页查询
     * @param string $name 表名
     * @param int $index 页索引
     * @param int $size 页大小
     * @param mixed $field 查询字段
     * @param mixed $where 查询条件
     * @param mixed $order 查询排序
     * @return array
     */
    protected static function page($name, $index, $size, $field = '*', $where = null, $order = null)
    {
        $result = db($name)->fetchSql(false)->field($field)->where(array_filter($where ?? []))->order($order)->paginate([
            'list_rows' => $size,
            'page' => $index
        ]);
        $list = $result->items();
        $count = $result->total();
        return ['rows' => $list, 'count' => $count, 'size' => $size, 'index' => $index];
    }

    /**
     * 查询数据
     * @param string $name 表名
     * @param mixed $where 查询条件
     * @param mixed $field 查询字段
     * @param mixed $order 查询排序
     * @return array
     */
    protected static function find($name, $field = '*', $where = null, $order = null)
    {
        if (is_int($where))
            $where = [['id', '=', $where]];
        return db($name)->field($field)->where($where)->order($order)->find();
    }

    /**
     * 查询键值表
     * @param string $name 表名
     * @param string $key 键名
     * @param string $value 值名
     * @return array
     */
    protected static function column($name, $key, $value)
    {
        return db($name)->column($key, $value);
    }

    /**
     * 保存数据
     * @param string $name
     * @param array $data
     * @return mixed
     */
    protected static function save($name, $data)
    {
        return db($name)->save($data);
    }

    /**
     * 插入数据
     * @param string $name
     * @param array $data
     * @return mixed
     */
    protected static function insert($name, $data)
    {
        return db($name)->insert($data);
    }

    /**
     * 更新数据
     * @param string $name
     * @param int $id
     * @param array $data
     * @return mixed
     */
    protected static function update($name, $id, $data)
    {
        return db($name)->where('id', $id)->update($data);
    }

    /**
     * 删除数据
     * @param string $name
     * @param int $id
     * @return bool
     */
    protected static function delete($name, $id)
    {
        return db($name)->delete($id);
    }

    /**
     * 原生查询
     * @param string $sql
     * @return array
     */
    protected static function query($sql)
    {
        return db()::query($sql);
    }

    /**
     * 原生更新
     * @param string $sql
     * @return bool
     */
    protected static function execute($sql)
    {
        return db()::execute($sql);
    }

}