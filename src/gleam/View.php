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

use \think\Template;

/**
 * 视图类
 * @link https://www.kancloud.cn/manual/think-template/content
 */
class View extends Template
{

    /**
     * @param App
     * @codeCoverageIgnore
     */
    public static function __make(App $app)
    {
        $config = array_merge([
            'view_path' => $app->getAppPath() . 'view' . DIRECTORY_SEPARATOR,
            'cache_path' => $app->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR,
            'view_suffix' => 'html',
        ], config('view') ?? [
                // 模板引擎类型使用Think
                'type' => 'Think',
                // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
                'auto_rule' => 1,
                // 模板目录名
                'view_dir_name' => 'view',
                // 模板后缀
                'view_suffix' => 'html',
                // 模板文件名分隔符
                'view_depr' => DIRECTORY_SEPARATOR,
                // 模板引擎普通标签开始标记
                'tpl_begin' => '{',
                // 模板引擎普通标签结束标记
                'tpl_end' => '}',
                // 标签库标签开始标记
                'taglib_begin' => '{',
                // 标签库标签结束标记
                'taglib_end' => '}',
            ]);
        if ($module_name = $app->getModuleName()) {
            $config['view_path'] = $app->getAppPath() . $module_name . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        }
        return new static($config);
    }

    /**
     * 获取模板内容
     * @access public
     * @param string $template 模板文件名或者内容
     * @param array $vars 模板变量
     * @param callable $filter 内容过滤
     * @return string
     * @throws \Exception
     */
    public function getFetch(string $template = '', array $vars = [], callable $filter = null): string
    {
        return $this->getContent(function () use ($template, $vars) {
            $this->fetch($template, $vars);
        }, $filter);
    }

    /**
     * 获取渲染内容
     * @access public
     * @param string $content 内容
     * @param array $vars 模板变量
     * @param callable $filter 内容过滤
     * @return string
     * @throws \Exception
     */
    public function getDisplay(string $content, array $vars = [], callable $filter = null): string
    {
        return $this->getContent(function () use ($content, $vars) {
            $this->display($content, $vars);
        }, $filter);
    }

    /**
     * 获取模板引擎渲染内容
     * @param $callback
     * @param $filter
     * @return string
     * @throws \Exception
     */
    protected function getContent($callback, $filter = null): string
    {
        // 页面缓存
        ob_start();
        if (PHP_VERSION > 8.0) {
            ob_implicit_flush(false);
        } else {
            ob_implicit_flush(0);
        }

        // 渲染输出
        try {
            $callback();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // 获取并清空缓存
        $content = ob_get_clean();

        if ($filter) {
            $content = call_user_func_array($filter, [$content]);
        }

        return $content;
    }

}
