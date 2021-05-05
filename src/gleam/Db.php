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

use \think\DbManager;

/**
 * 数据库管理类
 * @link https://www.kancloud.cn/manual/think-orm/content
 */
class Db extends DbManager
{

    /**
     * @return Db
     * @codeCoverageIgnore
     */
    public static function __make()
    {
        $db = new static();
        $db->setConfig(config('database') ?? [
                // 默认使用的数据库连接配置
                'default' => env('database.driver', 'mysql'),
                // 自定义时间查询规则
                'time_query_rule' => [],
                // 自动写入时间戳字段
                // true为自动识别类型 false关闭
                // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
                'auto_timestamp' => true,
                // 时间字段取出后的默认时间格式
                'datetime_format' => 'Y-m-d H:i:s',
                // 数据库连接配置信息
                'connections' => [
                    'mysql' => [
                        // 数据库类型
                        'type' => env('database.type', 'mysql'),
                        // 服务器地址
                        'hostname' => env('database.hostname', '127.0.0.1'),
                        // 数据库名
                        'database' => env('database.database', ''),
                        // 用户名
                        'username' => env('database.username', 'root'),
                        // 密码
                        'password' => env('database.password', ''),
                        // 端口
                        'hostport' => env('database.hostport', '3306'),
                        // 数据库连接参数
                        'params' => [],
                        // 数据库编码默认采用utf8
                        'charset' => env('database.charset', 'utf8'),
                        // 数据库表前缀
                        'prefix' => env('database.prefix', ''),
                        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
                        'deploy' => 0,
                        // 数据库读写是否分离 主从式有效
                        'rw_separate' => false,
                        // 读写分离后 主服务器数量
                        'master_num' => 1,
                        // 指定从服务器序号
                        'slave_no' => '',
                        // 是否严格检查字段是否存在
                        'fields_strict' => true,
                        // 是否需要断线重连
                        'break_reconnect' => false,
                        // 监听SQL
                        'trigger_sql' => env('app_debug', true),
                        // 开启字段缓存
                        'fields_cache' => false,
                    ],
                ],
            ]);
        return $db;
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
    public static function page1($name, $index, $size, $field = '*', $where = null, $order = null)
    {
        $result = db($name)->fetchSql(false)->field($field)->where(array_filter($where ?? []))->order($order)->paginate([
            'list_rows' => $size,
            'page' => $index
        ]);
        $list = $result->items();
        $count = $result->total();
        return ['rows' => $list, 'count' => $count, 'size' => $size, 'index' => $index];
    }

}
