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
 * 数据表格类
 */
class DataGrid extends Base
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

        // 获取输入参数
        parse_str(file_get_contents("php://input"), $input);
        $input = $this->option['input'] = array_merge($_GET, $input, $_POST);

        // 动作执行
        $method = $input['action'] ?? '';
        if ($method) {
            if (array_key_exists($method, $this->option['action'] ?? [])) {
                $this->option['msg'] = call_user_func($this->option['action'][$method], $input['id'] ?? 0);
            } elseif (array_key_exists($method, static::$hooks)) {
                $this->option['msg'] = call_user_func(static::$hooks[$method], $this->option['source'], $input['id'] ?? 0);
            }
        }

        // 数据表主键
        $pk = 'id';
        if (isset($this->option['pk']))
            $pk = $this->option['pk'];
        $this->option['pk'] = $pk;

        // 条目初始化
        foreach ($this->items as $name => $val) {
            // 如果值为字符串解析为数组
            if (is_string($val)) {
                if (strpos($val, 'idsel') === 0) {
                    // 选择模式转换
                    $this->items[$name] = [
                        'name' => '<input type="checkbox" onclick="$(\'input.idsel\').prop(\'checked\',$(this).prop(\'checked\'));">',
                        'value' => function ($r) use ($pk) {
                            $id = $r[$pk];
                            return '<input type="checkbox" class="idsel" value="' . $id . '">';
                        },
                        'width' => 40
                    ];
                } elseif (strpos($val, 'id') === 0) {
                    // ID模式转换
                    $this->items[$name] = [
                        'name' => strtoupper($pk),
                        'key' => $pk,
                        'width' => 60,
                        'sort' => true,
                        'search' => 0,
                    ];
                } elseif (strpos($val, 'time:') === 0) {
                    // 时间模式转换
                    $val = substr($val, 5);
                    $arr = explode('|', $val);
                    $this->items[$name] = [
                        'key' => $arr[0],
                        'value' => function ($v) use ($arr) {
                            $f = $arr[1] ?? 'Y-m-d H:i';
                            return date($f, strtotime($v));
                        },
                        'width' => 120
                    ];
                } else {
                    $arr = explode('|', $val);
                    $this->items[$name] = [
                        'key' => $arr[0],
                        'width' => $arr[1] ?? '',
                        'css' => $arr[2] ?? '',
                        'search' => (int)($arr[3] ?? '0'),
                    ];
                }
            }

            // 设置名称
            if (!isset($this->items[$name]['name']))
                $this->items[$name]['name'] = $name;
        }

        // 加载搜索
        $this->search();

        // 加载数据
        if ($data = $this->option['data'] ?? null) {
            $page = (int)($this->option['input']['page_index'] ?? 1);
            if ($page < 1)
                $page = 1;
            $filter = $this->option['search_filter'] ?? [];
            $sort = $this->option['input']['sort'] ?? null;
            if ($sort)
                $sort = str_replace('!', ' desc', $sort);
            if ($this->option['source'])
                $data = call_user_func(static::$hooks['data'], $this->option['source'], $page, $filter, $sort);
            else
                $data = call_user_func($data, $page, $filter, $sort);
            if ($data) {
                $this->option['rows'] = $data->items();
                $this->option['count'] = $data->total();
                $this->option['page_size'] = $data->listRows();
                $this->option['page_index'] = $data->currentPage();
            }
        }

        $this->option['msg'] = $_GET['msg'] ?? '';

    }

    private function url($query, $url = null)
    {
        $url = $url ?? $this->option['url'] ?? $_SERVER['REQUEST_URI'];
        $url = parse_url($url);
        $path = $url['path'] ?? '';
        $query = ($url['query'] ?? '') . '&' . $query;
        parse_str($query, $query);
        $query = http_build_query($query);
        return trim($path . '?' . $query, '?&');
    }

    private function swap($value)
    {
        if ($value) {
            if (strpos($value, '{$pop') !== false) {
                $value = preg_replace_callback('/\{\$pop\|([^\}]+)\}/',
                    function ($matches) {
                        $p = explode('|', $matches[1]);
                        $w = $p[2] ?? 500;
                        $h = $p[3] ?? 300;
                        return '<a href="javascript:;" data-popup="' . $p[1] . '" data-title="' . $p[0] . '" data-width="' . $w . '" data-height="' . $h . '">' . $p[0] . '</a>';
                    }, $value);
            }
            $value = str_replace('{$del}', '<a href="' . $this->url('action=del&id={$id}') . '" onclick="return confirm(\'您确定要执行此操作吗？\')">删除</a>', $value);
            $value = str_replace('{$edit}', '<a href="' . $this->url('action=edit&id={$id}') . '">编辑</a>', $value);
            $value = str_replace('{$view}', '<a href="' . $this->url('action=details&id={$id}') . '">查看</a>', $value);
        }
        return $value;
    }

    private function search()
    {

        $input = $this->option['input'];

        // 检查以下变量，不存在则初始化为空数组
        if (!isset($this->option['search']))
            $this->option['search'] = [];
        if (!isset($this->option['search_select']))
            $this->option['search_select'] = [];
        if (!isset($this->option['search_filter']))
            $this->option['search_filter'] = [];

        // 将条目内的搜索配置解析到search、search_selet变量
        foreach ($this->items as $item) {
            // 是否开启搜索，开启则判断变量类型
            if ($search = $item['search'] ?? false) {
                if (is_array($search)) {
                    // 数组类型直接追加
                    $this->option['search'] = array_merge($this->option['search'], $search);
                } elseif (is_string($search)) {
                    // 字符串类型可指定搜索键、搜索模式
                    $this->option['search'][$item['name']] = $search;
                } elseif (is_bool($search)) {
                    // 布尔类型判断是否存在items
                    if (!empty($item['items'])) {
                        // 存在显示为列表
                        $this->option['search'][] = [
                            'type' => 'select',
                            'items' => [$item['name'] => ''] + array_flip($item['items']),
                            'exec' => $item['key'],
                        ];
                    } else {
                        // 不存在为key模糊搜索
                        $this->option['search'][$item['name']] = $item['key'] . '%';
                    }
                } elseif (is_numeric($search)) {
                    // 数字类型
                    // 是负数增加为列表选择搜索模式
                    if ($search < 0)
                        $this->option['search_select']['按' . $item['name']] = $item['key'];
                    // 是正数按索引指定搜索模式，索引必须从2开始
                    switch (abs($search)) {
                        case 2:
                            $this->option['search'][$item['name']] = $item['key'] . '%';
                            break;
                        case 3:
                            $this->option['search'][$item['name']] = $item['key'];
                            break;
                    }
                }
            }
        }

        // 将列表选择搜索模式解析到search变量
        if ($items = $this->option['search_select']) {
            $this->option['search'][] = [
                'name' => '',
                'key' => 's_text',
                'type' => 'text',
                'width' => 150,
                'exec' => function ($k, $v) use ($input) {
                    $type = $input['s_type'];
                    if (!$type)
                        return null;
                    return [$type, 'like', '%' . $input['s_text'] . '%'];
                },
                'before' => [
                    'key' => 's_type',
                    'type' => 'select',
                    'items' => ['请选择' => ''] + $items,
                    'mode' => 2,
                ]
            ];
        }

        // 渲染搜索html
        $s = '';
        if ($items = $this->option['search']) {
            $s .= '<form autocomplete="off">';
            foreach ($items as $name => $item) {
                // 检查字符串
                if (is_string($item)) {
                    // html标签直接输出
                    if (preg_match('/^<.+>$/', $item)) {
                        $s .= $item;
                        continue;
                    }
                    // 其他转换为数组
                    $item = [
                        'key' => trim($item, '%><='),
                        'type' => 'text',
                        'width' => 100,
                        'exec' => $item,
                    ];
                }
                if (($input['action'] ?? '') == 'search') {
                    // 取得输入值
                    $val = $this->option['input'][$item['key']] ?? null;
                    // 如果值存在则填充search_filter变量
                    if ($val) {
                        $exec = $item['exec'] ?? $item['key'];
                        if (is_string($exec))
                            if ($operator = strpbrk($exec, '%><='))
                                $exec = [trim($exec, '%><='), str_replace('%', 'like', $operator), $val];
                            else
                                $exec = [$exec, '=', $val];
                        elseif ($exec instanceof \Closure)
                            $exec = call_user_func($exec, $item['key'], $val);
                        $this->option['search_filter'][] = $exec;
                    }
                }
                // 输出名称
                if (isset($item['name']))
                    $name = $item['name'];
                if ($name)
                    $s .= $name . '：';
                // 输出控件
                $s .= InputField::create($item, $input);
            }
            if (strpos($s, '<button') === false) {
                $s .= '<button type="submit"></button>';
            }
            $s .= '</form>';
        }

        $this->option['search_code'] = $s;

        return $s;
    }

    private function head()
    {
        $sorting = $this->option['input']['sort'] ?? '';
        $s = '';
        foreach ($this->items as $item) {

            $name = $item['name'] ?? '';
            $style = '';

            // 设置宽度，正数设置为最小宽度，负数设置为默认长度
            $w = (int)($item['width'] ?? 0);
            if ($w > 0) {
                $style .= 'min-width:' . $w . 'px;';
            } elseif ($w < 0) {
                $style .= 'width:' . (-$w) . 'px;';
            } else {
                $style .= 'min-width:200px;';
            }

            // 设置样式
            if ($style)
                $style = ' style="' . $style . '"';

            $s .= '<th scope="col"' . $style . '>';

            // 是否开启排序，默认开启
            if ($sort = $item['sort'] ?? true) {
                // 值为字符串指定为排序字段，为布尔值排序字段使用默认key
                if ($sort === true)
                    $sort = $item['key'] ?? '';
                // 有排序字段输出排序代码
                if ($sort) {
                    // 排序链接
                    $s .= '<a href="';
                    if ($sort === $sorting) {
                        $s .= $this->url('sort=' . $sort . '!');
                    } elseif ($sort . '!' === $sorting) {
                        $s .= $this->url('sort=' . $sort);
                    } else {
                        $s .= $this->url('sort=' . $sort);
                    }
                    $s .= '">' . $name . '</a>';
                    // 排序箭头
                    $s .= '<a href="' . $this->url('sort=' . $sort . '!') . '" class="arrow-up' . ($sorting === $sort . '!' ? ' arrow-sel' : '') . '"></a>';
                    $s .= '<a href="' . $this->url('sort=' . $sort) . '" class="arrow-down' . ($sorting === $sort ? ' arrow-sel' : '') . '"></a>';
                } else {
                    $s .= $name;
                }
            } else {
                $s .= $name;
            }

            $s .= '</th>';
        }
        return $s;
    }

    private function body()
    {
        $pk = $this->option['pk'];
        $s = '';
        if ($rows = $this->option['rows'] ?? null) {
            foreach ($rows as $r) {
                $s .= '<tr>';
                foreach ($this->items as $item) {

                    // 列引入样式
                    $css = $item['css'] ?? '';
                    if ($css)
                        $css = ' class="' . $css . '"';

                    // 列输出样式
                    $style = $item['style'] ?? '';
                    // 列长度
                    $w = (int)($item['width'] ?? 0);
                    if ($w > 0) {
                        $style .= 'width:' . $w . 'px;';
                    } elseif ($w < 0) {
                        $style .= 'min-width:' . (-$w) . 'px;';
                    } else {
                        $style .= 'min-width:200px;';
                    }
                    if ($style)
                        $style = ' style="' . $style . '"';

                    // 列输出值
                    $value = $item['value'] ?? null;

                    // 如果是函数开始执行
                    if ($value instanceof \Closure) {
                        // 有key作为函数的第一个参数
                        if ($item['key'] ?? null)
                            $value = call_user_func_array($value, [$r[$item['key']] ?? null, $r]);
                        else
                            $value = call_user_func_array($value, [$r]);
                    }

                    // 解析列输出值参数标签
                    if ($value) {
                        // 返回是数组处理成字符串
                        if (is_array($value)) {
                            $value = implode('<span class="separate">|</span>', $value);
                        }
                    }

                    // 列输出值不存在去数据集中查找
                    if ($value === null && $key = $item['key'] ?? '') {
                        $i = strpos($key, '.');
                        $k = substr($key, $i ? $i + 1 : 0);
                        $value = $r[$k] ?? null;
                    }

                    // 如果设置了items将值做映射转换，最多查询2层
                    if ($items = $item['items'] ?? null) {
                        foreach ($items as $k1 => $v1) {
                            if (is_array($v1)) {
                                foreach ($v1 as $k2 => $v2) {
                                    if ($k2 === $value) {
                                        $value = $v2;
                                        break 2;
                                    }
                                }
                            } else {
                                if ($k1 === $value) {
                                    $value = $v1;
                                    break 1;
                                }
                            }
                        }
                    }

                    // 如果设置了format将值做格式化处理
                    if ($format = $item['format'] ?? '')
                        $value = sprintf($format, $value);

                    // 变量分析
                    if (is_string($value)) {
                        $value = $this->swap($value);
                        $value = str_replace('%7B%24id%7D', $r[$pk], $value);
                        $value = str_replace('{$id}', $r[$pk], $value);
                    }

                    // 输出列代码
                    $s .= "<td{$css}{$style}>{$value}</td>";

                }
                $s .= '</tr>';
            }
        } else {
            $s = '<tr><td colspan="' . (count($this->items)) . '" class="no-data"></td></tr>';
        }
        return $s;
    }

    private function page()
    {
        // 翻页参数
        $index = (int)$this->option['page_index'];
        $size = (int)$this->option['page_size'];
        $count = (int)$this->option['count'];
        $total = $size ? ceil($count / $size) : $count;

        // 翻页变量
        $btn = ['', '', '', '', ''];
        $btn[0] = $index > 1 ? '<a href="' . $this->url('page_index=1') . '" class="page-first"></a>' : '<a class="page-first"></a>';
        $btn[1] = $index > 1 ? '<a href="' . $this->url('page_index=' . ($index - 1)) . '" class="page-up"></a>' : '<a class="page-up"></a>';
        $btn[3] = $index < $total ? '<a href="' . $this->url('page_index=' . ($index + 1)) . '" class="page-down"></a>' : '<a class="page-down"></a>';
        $btn[4] = $index < $total ? '<a href="' . $this->url('page_index=' . $total) . '" class="page-last"></a>' : '<a class="page-last"></a>';

        // 翻页范围
        $range = (int)($this->option['page_range'] ?? 5) - 1;
        $start = $index - $range;
        if ($start < 1)
            $start = 1;
        $end = $index + $range;
        if ($end > $total)
            $end = $total;
        for ($i = $start; $i <= $end; $i++) {
            $btn[2] .= '<a href="' . $this->url('page_index=' . $i) . '" class="';
            if ($index == $i)
                $btn[2] .= 'page-num active';
            else
                $btn[2] .= 'page-num';
            $btn[2] .= '">' . $i . '</a>';
        }

        $code = $this->option['page'] ?? '{$page_first}{$page_up}{$page_range}{$page_down}{$page_last}共有 {$count} 条记录';
        if ($code instanceof \Closure)
            $code = call_user_func($code, $index, $size, $count, $total, $btn);

        $s = $code;
        $s = str_replace('{$count}', $count, $s);
        $s = str_replace('{$page_first}', $btn[0], $s);
        $s = str_replace('{$page_up}', $btn[1], $s);
        $s = str_replace('{$page_range}', $btn[2], $s);
        $s = str_replace('{$page_down}', $btn[3], $s);
        $s = str_replace('{$page_last}', $btn[4], $s);

        return $s;
    }

    private function nav()
    {
        $nav = $this->option['nav'] ?? ['top' => '', 'bottom' => ''];
        if (is_string($nav))
            $nav = ['top' => $nav, 'bottom' => ''];
        foreach ($nav as $k => $v) {
            if (is_array($v)) {
                $v = implode(' ', $v);
            }
            $v = str_replace('{$add}', '<a href="?action=add">添加</a>', $v);
            $v = $this->swap($v);
            $nav[$k] = $v;
        }
        return $nav;
    }

    private function whole($head, $body, $page, $nav, $search, $title, $msg)
    {
        $code = $this->option['code'] ?? function ($head, $body, $page, $nav, $search, $title, $msg) {
                $s = '';
                $s .= '<div class="datagrid">';
                if ($msg)
                    $s .= '<div class="alert alert-success" data-delayclose="3000">' . $msg . '<a href="javascript:;" class="alert-close"></a></div>';
                if ($title)
                    $s .= '<div class="title"><b>' . $title . '</b></div>';
                if ($nav['top'] . $search) {
                    $s .= '<div class="datagrid-head">';
                    if ($nav['top'])
                        $s .= '<div class="datagrid-nav">' . $nav['top'] . '</div>';
                    if ($search)
                        $s .= '<div class="datagrid-search">' . $search . '</div>';
                    $s .= '</div>';
                }
                $s .= '<table class="datagrid-table">';
                $s .= '<thead><tr>' . $head . '</tr></thead>';
                $s .= '<tbody>' . $body . '</tbody>';
                $s .= '</table>';
                if ($nav['bottom'] . $page) {
                    $s .= '<div class="datagrid-foot">';
                    if ($nav['bottom'])
                        $s .= '<div class="datagrid-nav">' . $nav['bottom'] . '</div>';
                    if ($page)
                        $s .= '<div class="datagrid-page">' . $page . '</div>';
                    $s .= '</div>';
                }
                $s .= '</div>';
                return $s;
            };
        return call_user_func($code, $head, $body, $page, $nav, $search, $title, $msg);
    }

    /**
     * 输出
     * @access public
     * @return string
     */
    public function output(): string
    {
        $head = $this->head();
        $body = $this->body();
        $page = $this->page();
        $nav = $this->nav();
        $s = $this->before ?? '';
        $s .= $this->whole($head, $body, $page, $nav, $this->option['search_code'] ?? '', $this->option['title'] ?? '', $this->option['msg'] ?? '');
        $s .= $this->after ?? '';
        return $s;
    }

}
