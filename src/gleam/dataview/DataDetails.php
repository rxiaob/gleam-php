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
 * 数据详情类
 */
class DataDetails extends Base
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
        $this->data();
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
     * 解析
     * @access public
     * @param array $items 条目
     * @param array $data 数据
     * @param string $format 格式化
     * @return string
     */
    public static function parse($items, $data = null, $format = null)
    {
        $s = '';
        if (!empty($items)) {
            if (!is_array($items))
                $items = [$items];
            foreach ($items as $k => $v) {
                if (empty($v))
                    continue;
                if ($k === 'css1') {
                    $s = '<style>' . $v . '</style>';
                    continue;
                }
                if ($k === 'format') {
                    $format = $v;
                    continue;
                }
                $tag = $k;
                $text = '';
                $css = '';
                $style = '';
                if (is_array($v)) {
                    if ($v['tag'] ?? null)
                        $tag = $v['tag'];
                    if ($v['text'] ?? null)
                        $text = $v['text'];
                    if ($v['format'] ?? null)
                        $format = $v['format'];
                    if ($css = $v['css'] ?? null) {
                        $css = " class=\"{$css}\"";
                    }
                    if ($style = $v['style'] ?? null) {
                        $style = " style=\"{$style}\"";
                    }
                    unset($v['css']);
                    unset($v['style']);
                }
                switch ((string)$tag) {
                    case 'tab':
                        $index = (int)($v['index'] ?? 0);
                        unset($v['index']);
                        $id = 'tabs' . rand(0, 999999);
                        $items = $v['items'] ? $v['items'] : $v;
                        $s .= '<div id="' . $id . '"' . $css . $style . '>';
                        $s .= '<nav>';
                        $i = 0;
                        foreach ($items as $k2 => $v2) {
                            $s .= '<a href="#" class="' . ($index === $i ? 'sel' : '') . '" onclick="$(\'#' . $id . '>nav a\').removeClass(\'sel\');$(this).addClass(\'sel\');$(\'#' . $id . '>div\').hide();$(\'#' . $id . '>div:eq(' . $i . ')\').show();">' . $k2 . '</a>';
                            $i++;
                        }
                        $s .= '</nav>';
                        $i = 0;
                        foreach ($items as $k2 => $v2) {
                            $style = '';
                            if ($index !== $i)
                                $style = 'display:none;';
                            $s .= '<div style="' . $style . '">';
                            $s .= self::parse($v2, $data, $format);
                            $s .= '</div>';
                            $i++;
                        }
                        $s .= '</div>';
                        break;
                    case 'iframe':
                        $width = $v['width'] ?? '100%';
                        $height = $v['height'] ?? '100%';
                        $s .= '<iframe src="' . ($v['src'] ?? '') . '"' . $css . $style . ' width="' . $width . '" height="' . $height . '" onload="' . ($v['onload'] ?? '') . '" frameborder="0" scrolling="auto" allowtransparency="true"></iframe>';
                        break;
                    case 'table':
                        if (!$format)
                            $format = '<tr><th>%s</th><td>%s</td></tr>';
                    case 'div':
                    case 'p':
                    case 'dl':
                    case 'dt':
                    case 'dd':
                    case 'ul':
                    case 'ol':
                    case 'li':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                    case 'span':
                    case 'b':
                    case 'i':
                        $s .= "<{$tag}{$css}{$style}>";
                        $code = self::parse(!empty($v['items']) ? $v['items'] : $v, $data, $format);
                        $s .= $text ? sprintf($text, $code) : $code;
                        $s .= "</{$tag}>";
                        break;
                    default:
                        $name = $k;
                        $key = $v;
                        if (is_array($v)) {
                            if ($v['name'] ?? null)
                                $name = $v['name'];
                            if ($v['key'] ?? null)
                                $key = $v['key'];
                        }
                        if (is_numeric($name) && is_string($key))
                            $name = $key;
                        $value = '';
                        if ($key instanceof \Closure)
                            $value = call_user_func_array($key, [$data]);
                        else if ($data)
                            if ($data instanceof \Closure)
                                $value = call_user_func_array($data, [$key]);
                            else if (is_array($data))
                                $value = $data[$key];
                        if ($format) {
                            if (substr_count($format, '%s') == 2) {
                                $s .= sprintf($format, $name, $value);
                            } else {
                                $s .= sprintf($format, $value);
                            }
                        } else {
                            $s .= $value;
                        }
                        break;
                }
            }
            if (strpos($s, '{imgautowidth'))
                $s = preg_replace('/\{imgautowidth\|(\d+)\}/', 'onload="if(this.width>$1){ this._width=this.width;this.width=$1 }" onclick="if(this.width==$1){ this.width=this._width }else{ this.width=$1 }"', $s);
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
        $s = '<div class="dataderails">';
        if ($title = $this->option['title'] ?? '')
            $s .= '<div class="dataderails-title"><b>' . $title . '</b></div>';
        $s .= self::parse($this->items, $this->option['data'] ?? null, $this->option['format'] ?? null);
        $s .= '</div>';
        return $s;
    }

}
