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

namespace gleam\dataview;

/**
 * 数据视图基类
 */
abstract class Base
{

    protected static $hooks = [];

    /**
     * 设置数据处理方法
     * @param array $hooks
     */
    public static function hook($hooks)
    {
        static::$hooks = $hooks;
    }

    protected $items;
    protected $option;
    protected $input;

    /**
     * 构造器
     * @access public
     * @param array $items 条目
     * @param array $option 配置
     */
    public function __construct($items, $option = null)
    {
        $this->items = $items;
        $this->option = $option;

        // 获取输入参数
        parse_str(file_get_contents("php://input"), $this->input);
        $this->input = array_merge($_GET, $this->input, $_POST);

        // 加载数据
        if ($data = $this->option['data'] ?? null) {

            // 解析数据源
            $source = [];
            if (is_string($data))
                $source = ['name' => $data];
            elseif (is_array($data))
                $source = $data;
            $this->option['source'] = $source;

            if (!($data = $this->option['count'] ?? null) && $count = static::$hooks['count'] ?? null) {
                $this->option['count'] = function ($k, $v) use ($count, $source) {
                    return call_user_func_array($count, [$source, $k, $v]);
                };
            }

        }

    }

    /**
     * 加载数据
     * @access protected
     */
    protected function data()
    {
        if ($data = $this->option['data'] ?? null) {

            $source = $this->option['source'];

            // 解析数据
            $id = (int)($_GET['id'] ?? '0');
            if ($source)
                $data = call_user_func(static::$hooks['data'], $source, $id);
            if ($data instanceof \Closure)
                $data = call_user_func($data, $id);
            return $this->option['data'] = $data;
        }
    }

    /**
     * 快速创建
     * @access public
     * @param array $items 条目
     * @param array $option 配置
     */
    public static function create($items, $option = null)
    {
        return new static($items, $option);
    }

    /**
     * 输出
     * @access public
     * @return string
     */
    public function output(): string
    {
        return '';
    }

    public function __set(string $name, $value)
    {
        $this->option[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->option[$name] ?? null;
    }

    // 注意：文本输出发生在需要转换为字符串的地方，直接将类初始化赋予变量并不会发生主动转换。
    public function __toString(): string
    {
        return $this->output();
    }

}
