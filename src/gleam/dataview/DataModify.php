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

use think\Exception;

/**
 * 数据调整视图类
 */
class DataModify extends Base
{

    protected static $hooks = [];

    /**
     * 构造器
     * @access public
     * @param array $items 条目
     * @param array $option 配置
     */
    public function __construct($items, $option = null)
    {
        parent::__construct($items, $option);
        if (($title = $this->option['title'] ?? null) && $title instanceof \Closure)
            $this->option['title'] = call_user_func($title, (int)($_GET['id'] ?? '0'));

    }

    private function save($items, $option)
    {
        $result = null;

        // 检测到POST操作开始处理
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {

            $data = [];

            // 数据源
            $query = $_POST;

            // 检测到错误做失败处理
            try {
                // 填充数据
                array_walk_recursive($items, function ($value, $key) use (&$data, &$query, &$option) {
                    if ($key === 'key')
                        $data[$value] = $query[$value] ?? null;
                    if ($key === 'unique' && $count = $this->option['count']) {
                        $v = end($data);
                        $k = key($data);
                        if (call_user_func_array($count, [$k, $v]))
                            err(str_replace('$1', $v, $value));
                    }
                });

                $id = (int)($_GET['id'] ?? '0');

                // 执行ing事件
                if ($ing = $option['ing'] ?? null)
                    $result = call_user_func_array($ing, [&$data, $id]);

                // ing事件无有效返回数据开始保存数据
                if (!$result) {
                    // 保存数据需要返回数据id
                    if (isset($option['save'])) {
                        // 执行save事件
                        $id = call_user_func($option['save'], $data, $id);
                    } elseif ($option['source'] && isset(static::$hooks['save'])) {
                        // 执行钩子save事件
                        $id = call_user_func(static::$hooks['save'], $option['source'], $data, $id);
                    }
                }

                // 执行ed事件
                if ($ed = $option['ed'] ?? null) {
                    if ($rst = call_user_func($ed, $id, $data)) {
                        $result = $rst;
                    }
                }

                // 返回值处理
                if (empty($result))
                    $result = ['status' => 200, 'msg' => '操作成功'];
                elseif (is_string($result)) {
                    // 执行js
                    if (strpos($result, 'js:') === 0) {
                        $js = substr($result, 3);
                        $js = str_replace('popup_close();', 'window.parent.location.reload();', $js);
                        exit('<script>' . $js . '</script>');
                    }
                    // 执行消息跳转
                    if (strpos($result, 'msg:') === 0) {
                        $msg = substr($result, 4);
                        go('?msg=' . urlencode($msg));
                    }
                    // 执行地址跳转
                    if (strpos($result, 'http') === 0 || strpos($result, '/') === 0 || strpos($result, '?') === 0)
                        go($result);
                    $result = ['status' => 200, 'msg' => $result];
                }
            } catch (\Exception $e) {
                $result = ['status' => 400, 'msg' => $e->getMessage()];
            }

        }

        return $result;
    }

    private function body($items, $data)
    {
        if ($items instanceof \Closure)
            return call_user_func($items, $data);
        $s = '';
        if (!(array_key_exists('tabs', $items) || array_key_exists('step', $items))) {
            $s .= '<ul class="datamodify-items">';
        }
        foreach ($items as $name => $item) {
            if ($name === 'tabs' || $name === 'step') {
                $s .= '<dl class="tabs tabs-simple' . ($name === 'step' ? ' tabs-step' : '') . '">';
                $s .= '<dt>';
                $i = 0;
                foreach ($item as $tabname => $tab) {
                    $s .= '<a href="javascript:;" ' . ($i == 0 ? ' class="active"' : '') . '>' . $tabname . '</a>';
                    $i++;
                }
                $s .= '</dt>';
                foreach ($item as $tab) {
                    $s .= '<dd>';
                    $s .= $this->body($tab, $data);
                    $s .= '</dd>';
                }
                $s .= '</dl>';
                continue;
            }
            if (!is_array($item))
                continue;
            $enable = $item['enable'] ?? true;
            if (!$enable)
                continue;
            $type = $item['type'] ?? 'text';
            if (isset($item['name']))
                $name = $item['name'];
            $key = $item['key'] ?? null;
            if (isset($key)) {
                $item['key'] = is_array($key) ? $key : [$key];
            }
            switch ($type) {
                case 'group':
                    $s .= '<li class="datamodify-group">';
                    $s .= $name;
                    $s .= '</li>';
                    break;
                default:
                    $s .= '<li';
                    if ($type == 'hidden') {
                        $s .= ' style="display: none;"';
                    }
                    $s .= '>';
                    $s .= '<span class="datamodify-caption">';
                    $s .= $name;
                    $s .= '</span>';
                    $keys = $item['key'] ?? null;
                    if ($keys) {
                        $keys_count = count($keys);
                        foreach ($keys as $i => $key) {
                            $option = $item;
                            $option['key'] = $key;
                            $s .= InputField::create($option, $data);
                            if ($i < ($keys_count - 1))
                                $s .= $item['separate'] ?? '';
                        }
                    } elseif ($item['value']) {
                        $s .= $item['value']($data);
                    }
                    $s .= '</li>';
                    break;
            }
        }
        if (!(array_key_exists('tabs', $items) || array_key_exists('step', $items))) {
            $s .= '</ul>';
        }
        return $s;
    }

    private function whole($msg, $body): string
    {
        $code = $this->option['code'] ?? function ($body, $title, $submit, $msg) {
                $s = '';
                if ($title)
                    $s .= "<div class='title'><b>{$title}</b></div>";
                $s .= '<form action="" method="post" autocomplete="off" class="datamodify">';
                if ($msg) {
                    if (is_string($msg))
                        $s .= '<div class="alert alert-success">' . $msg . '<a href="javascript:;" class="alert-close"></a></div>';
                    else if ($msg['status'] === 200) {
                        $s .= '<div class="alert alert-success">' . $msg['msg'] . '<a href="javascript:;" class="alert-close"></a></div>';
                    } else {
                        $s .= '<div class="alert alert-failed">' . $msg['msg'] . '<a href="javascript:;" class="alert-close"></a></div>';
                    }
                }
                $s .= $body;
                $s .= '<div class="datamodify-foot">';
                $s .= '<button type="submit" class="datamodify-foot-submit">' . $submit . '</button>';
                $s .= '</div>';
                $s .= '</form>';
                return $s;
            };
        $s = call_user_func($code, $body, $this->option['title'] ?? '', $this->option['submit_btn'] ?? '提交', $msg);
        if ($js = $this->option['js'] ?? '') {
            $s .= '<script>';
            $s .= $js;
            $s .= '</script>';
        }
        return $s;
    }

    /**
     * 输出
     * @access public
     * @return string
     */
    public function output(): string
    {
        $msg = $this->save($this->items, $this->option) ?? $_GET['msg'] ?? null;
        $data = $this->data();
        $body = $this->body($this->items, $data);
        return $this->whole($msg, $body);
    }

}
