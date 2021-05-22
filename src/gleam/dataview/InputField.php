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
 * 表单控件类
 */
class InputField
{

    protected static $fields = [];

    /**
     * 设置数据处理方法
     * @param array $fields
     */
    public static function field($fields)
    {
        static::$fields = $fields;
    }

    private $option;

    private $key;
    private $value;
    private $type;
    private $width;
    private $height;
    private $unit;
    private $before;
    private $after;

    /**
     * 构造器
     * @access public
     * @param array $option 配置
     * @param array $data 数据
     */
    public function __construct($option, $data = null)
    {
        $this->option = $option;

        // 数据键
        $this->key = $option['key'] ?? null;

        // 数据值
        $this->value = $option['value'] ?? null;
        if ($data && isset($data[$this->key]))
            $this->value = $data[$this->key];

        // 类型
        $this->type = $option['type'] ?? 'text';

        // 长度
        $this->width = (int)($option['width'] ?? null);

        // 高度
        $this->height = (int)($option['height'] ?? null);

        // 单位
        $this->unit = $option['unit'] ?? '';

        // 前置内容
        $this->before = $option['before'] ?? '';
        if ($this->before && is_array($this->before)) {
            $this->before = self::create($this->before, $data);
        }

        // 后置内容
        $this->after = @$option['after'] ?? '';
        if ($this->after && is_array($this->after)) {
            $this->after = self::create($this->after, $data);
        }

    }

    /**
     * 快速创建
     * @access public
     * @param array $option 配置
     * @param array $data 数据
     */
    public static function create($option, $data = null)
    {
        return new static($option, $data);
    }

    /**
     * 输入框
     * @access public
     * @param string $type 类型
     * @param string $name 名称
     * @param string $value 值
     * @param string $attach 附加属性
     * @param string $placeholder 提示信息
     * @return string
     */
    public static function input($type, $name, $value = '', $attach = '', $placeholder = ''): string
    {
        $s = '<input type="' . $type . '"';
        if ($name)
            $s .= ' name="' . $name . '"';
        if ($value)
            $s .= ' value="' . $value . '"';
        if ($placeholder)
            $s .= ' placeholder="' . $placeholder . '"';
        if ($attach = trim($attach))
            $s .= ' ' . $attach;
        $s .= '/>';
        return $s;
    }

    /**
     * 文本框
     * @access public
     * @param string $name 名称
     * @param string $value 值
     * @param string $attach 附加属性
     * @param string $placeholder 提示信息
     * @return string
     */
    public static function textarea($name, $value = '', $attach = '', $placeholder = ''): string
    {
        $s = '<textarea';
        if ($name)
            $s .= ' name="' . $name . '"';
        if ($placeholder)
            $s .= ' placeholder="' . $placeholder . '"';
        if ($attach = trim($attach))
            $s .= ' ' . $attach;
        $s .= '/>';
        if ($value)
            $s .= htmlspecialchars($value);
        $s .= '</textarea>';
        return $s;
    }

    /**
     * 列表框
     * @access public
     * @param string $name 名称
     * @param array $items 条目
     * @param string $value 选中值
     * @param string $attach 附加属性
     * @return string
     */
    public static function select($name, $items, $value = '', $attach = ''): string
    {
        $s = '<select';
        if ($name)
            $s .= ' name="' . $name . '"';
        if ($attach = trim($attach))
            $s .= ' ' . $attach;
        $s .= '>';
        if ($items) {
            foreach ($items as $k => $v) {
                if (is_array($v)) {
                    $s .= '<optgroup label="' . $k . '">';
                    foreach ($v as $k1 => $v1) {
                        $s .= '<option value="' . $v1 . '"';
                        if ((string)$v1 === (string)$value) {
                            $s .= ' selected="selected"';
                        }
                        $s .= '>' . $k1 . '</option>';
                    }
                    $s .= '</optgroup>';
                } else {
                    $s .= '<option value="' . $v . '"';
                    if ((string)$v === (string)$value) {
                        $s .= ' selected';
                    }
                    $s .= '>' . $k . '</option>';
                }
            }
        }
        $s .= '</select>';
        return $s;
    }

    /**
     * 单选框
     * @access public
     * @param string $name 名称
     * @param array $items 条目
     * @param string $value 选中值
     * @param string $attach 附加属性
     * @return string
     */
    public static function radio($name, $items, $value = '', $attach = ''): string
    {
        $s = '';
        foreach ($items as $k => $v) {
            $s .= '<label>';
            $s .= '<input type="radio"';
            if ($name)
                $s .= ' name="' . $name . '"';
            $s .= ' value="' . $name . '"';
            if ((string)$v === (string)$value) {
                $s .= ' checked="checked"';
            }
            $s .= ' title="' . $k . '"';
            if ($attach = trim($attach))
                $s .= ' ' . $attach;
            $s .= '/>';
            $s .= $k;
            $s .= '</label>';
        }
        return $s;
    }

    /**
     * 复选框
     * @access public
     * @param string $name 名称
     * @param array $items 条目
     * @param string $value 选中值
     * @param string $attach 附加属性
     * @return string
     */
    public static function checkbox($name, $items, $value = '', $attach = ''): string
    {
        $value = $value ? explode(',', $value) : [];
        $s = '';
        foreach ($items as $k => $v) {
            $s .= '<label>';
            $s .= '<input type="checkbox"';
            if ($name)
                $s .= ' name="' . $name . '"';
            $s .= ' value="' . $name . '"';
            if (in_array($v, $value)) {
                $s .= ' checked="checked"';
            }
            $s .= ' title="' . $k . '"';
            if ($attach = trim($attach))
                $s .= ' ' . $attach;
            $s .= '/>';
            $s .= $k;
            $s .= '</label>';
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
        $s = '';
        $s .= $this->before;
        $placeholder = $this->option['placeholder'] ?? '';
        if ($field = static::$fields[$this->type] ?? null) {
            $s .= call_user_func($field, $this, $this->option);
        } else {
            $style = $this->option['style'] ?? '';
            $attach = $this->option['attach'] ?? '';
            if ($empty = $this->option['empty'] ?? '')
                $attach .= ' data-empty="' . $empty . '"';
            if ($verify = $this->option['verify'] ?? '')
                $attach .= ' data-verify="' . $verify . '" data-error="' . ($this->option['error'] ?? '') . '"';
            switch ($this->type) {
                case 'text':
                case 'password':
                case 'search':
                case 'email':
                case 'tel':
                case 'url':
                    $w = $this->width ?: 300;
                    $attach = trim("style=\"width:{$w}px;{$style}\" autocomplete=\"new-password\" {$attach}");
                    $s .= self::input($this->type, $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'textarea':
                    $w = $this->width ?: 500;
                    $h = $this->height ?: 100;
                    $attach = trim("style=\"width:{$w}px;height:{$h}px;{$style}\" {$attach}");
                    $s .= self::textarea($this->key, $this->value, $attach, $placeholder);
                    break;
                case 'number':
                    $min = $this->option['min'] ?? '';
                    $max = $this->option['max'] ?? '';
                    $step = $this->option['step'] ?? 1;
                    $w = $this->width ?: 100;
                    $attach = "min=\"{$min}\" max=\"{$max}\" step=\"{$step}\" style=\"width:{$w}px;{$style}\" {$attach}";
                    $s .= self::input('number', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'int':
                    $w = $this->width ?: 100;
                    $attach = "style=\"width:{$w}px;{$style}\" onkeyup=\"if(!/^[1-9\-]{1}\d*$/g.test(this.value)){ this.value=parseInt(this.value).toString().replace(/NaN/,''); }\" {$attach}";
                    $s .= self::input('text', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'float':
                    $w = $this->width ?: 100;
                    $attach = "style=\"width:{$w}px;{$style}\" onkeyup=\"if(!/^[\d\.\-]+$/g.test(this.value)){ this.value=parseFloat(this.value).toString().replace(/NaN/,''); }\" {$attach}";
                    $s .= self::input('text', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'year':
                case 'month':
                case 'date':
                case 'time':
                case 'datetime':
                    if ($this->type == 'datetime') {
                        $w = $this->width ?: 150;
                    } else {
                        $w = $this->width ?: 100;
                    }
                    if (strtotime($this->value ?? '') === -62170013143)
                        $this->value = '';
                    $attach = "data-input=\"{$this->type}\" style=\"width:{$w}px;{$style}\" {$attach}";
                    $s .= self::input('text', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'slide':
                case 'range':
                    $min = $this->option['min'] ?? 0;
                    $max = $this->option['max'] ?? 100;
                    $step = $this->option['step'] ?? 1;
                    $w = $this->width ?: 300;
                    $attach = "min=\"{$min}\" max=\"{$max}\" step=\"{$step}\" style=\"width:{$w}px;{$style}\" {$attach}";
                    $s .= self::input('range', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'color':
                    $w = $this->width ?: 100;
                    $attach = "style=\"width:{$w}px;{$style}\" {$attach}";
                    $s .= self::input('color', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'select':
                case 'select2':
                    $w = $this->width ?: 100;
                    $mode = (int)($this->option['mode'] ?? 0);
                    $items = $this->option['items'] ?? null;
                    if ($this->type === 'select2') {
                        $data = $this->option['data'] ?? [];
                        if ($data) {
                            $arr = [];
                            foreach ($data as $k => $v)
                                $arr[] = [
                                    'id' => $v,
                                    'text' => $k
                                ];
                            $data = $arr;
                        }
                        $attach .= ' data-input="' . $this->type . '" data-source="' . urlencode(json_encode($data)) . '" data-value="' . $this->value . '"';
                    }
                    if ($items && $mode === 1)
                        $items = array_flip($items);
                    $attach = "style=\"width:{$w}px;{$style}\" {$attach}";
                    $s .= self::select($this->key, $items, $this->value, $attach);
                case 'radio':
                case 'checkbox':
                    $mode = (int)($this->option['mode'] ?? 0);
                    $items = $this->option['items'] ?? null;
                    if ($items && $mode === 1)
                        $items = array_flip($items);
                    if ($style)
                        $attach = trim(" style=\"{$style}\" {$attach}");
                    if ($this->type == 'radio')
                        $s .= self::radio($this->key, $items, $this->value, $attach);
                    elseif ($this->type == 'checkbox')
                        $s .= self::checkbox($this->key, $items, $this->value, $attach);
                    break;
                case 'ce':
                case 'ckeditor':
                case 'ke':
                case 'kindeditor':
                    if ($this->type === 'ce')
                        $this->type = 'ckeditor';
                    if ($this->type === 'ke')
                        $this->type = 'kindeditor';
                    $w = $this->width ? $this->width . 'px' : '100%';
                    $h = $this->height ? $this->height . 'px' : '300px';
                    $items = $this->option['items'] ?? '';
                    $attach = "data-input=\"{$this->type}\" data-items=\"{$items}\" style=\"width:{$w};height:{$h};{$style}\" {$attach}";
                    $s .= self::textarea($this->key, $this->value, $attach, $placeholder);
                    break;
                case 'map':
                    $w = $this->width ? $this->width . 'px' : '100%';
                    $h = $this->height ? $this->height . 'px' : '400px';
                    $city = $this->option['city'] ?? '';
                    $zoom = $this->option['zoom'] ?? '';
                    $hook = $this->option['hook'] ?? '';
                    $attach = "data-input=\"{$this->type}\" data-city=\"{$city}\" data-zoom=\"{$zoom}\" data-hook=\"{$hook}\" style=\"width:{$w};height:{$h};{$style}\" {$attach}";
                    $s .= self::input('hidden', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'upimage':
                    $attach .= ' data-btn="上传图片"';
                case 'upfile':
                case 'upload':
                    $w = $this->width ?: 300;
                    $mode = (int)($this->option['mode'] ?? 0);
                    $accept = $this->option['items'] ?? '.jpg,.png';
                    $attach = trim("data-input=\"upload\" style=\"width:{$w}px;{$style}\" data-accept=\"{$accept}\"" . ($mode === 1 ? ' multiple="multiple"' : '') . ' ' . $attach);
                    $s .= self::input('text', $this->key, $this->value, $attach, $placeholder);
                    break;
                case 'switch':
                    $items = explode('|', ($this->option['items'] ?? '') . '|');
                    $attach = "data-no=\"{$items[0]}\" data-yes=\"{$items[1]}\"" . ($this->value ? ' checked="checked"' : '') . ' ' . $attach;
                    $s .= "<label class='switch'><input type=\"checkbox\" name=\"{$this->key}\" value=\"1\" {$attach}/></label>";
                    break;
                case 'tags':
                    $w = $this->width ?: 500;
                    $h = $this->height ?: 34;
                    $delimiter = $this->option['delimiter'] ?? ',';
                    $attach = "data-input=\"{$this->type}\" data-delimiter=\"{$delimiter}\" style=\"width:{$w}px;height:{$h}px;{$style}\" {$attach}";
                    $s .= self::input('text', $this->key, $this->value, $attach, $placeholder);
                    break;
                default:
                    $s .= '<label>';
                    $s .= $this->value;
                    $s .= "<input type=\"hidden\" name=\"{$this->key}\" value=\"{$this->value}\"/>";
                    $s .= '</label>';
            }
        }
        if ($this->unit) {
            $s .= ' ' . $this->unit;
        }
        $s .= $this->after;
        return $s;
    }

    public function __toString(): string
    {
        return $this->output();
    }

}
