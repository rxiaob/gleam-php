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

namespace gleam\admin;

class Auth
{

    protected $xml;

    /**
     * 构造器
     * @param string $xmlFile
     */
    public function __construct($xmlFile)
    {
        $this->xml = new \DOMDocument();
        $this->xml->load($xmlFile);
    }

    /**
     * 导航列表
     * @param array $rules
     * @param string $host
     * @return array
     */
    public function nav($rules = null, $host = '')
    {
        return $this->nav_nodes($this->xml->documentElement, $rules, $host);
    }

    private function nav_nodes(&$element, &$rules, &$host)
    {
        $nodes = [];
        foreach ($element->childNodes as $e) {
            if (!($e instanceof \DOMElement && $e->tagName == 'nav'))
                continue;
            $auth = $e->getAttribute('auth');
            if ($auth === '')
                $auth = '1';
            $module = '';
            $p = $e;
            do {
                if ($p && $p instanceof \DOMElement && $p->hasAttribute('module')) {
                    $module = $p->getAttribute('module');
                    break;
                }
            } while ($p = $p->parentNode);
            $name = $e->getAttribute('name');
            $url = $e->getAttribute('url');
            if ($url)
                $url = trim($module . '.' . $host, '.') . '/' . $url . '/';
            if (!$auth || self::privilege($rules, $module, $url)) {
                $nodes[$name] = [
                    'url' => $url,
                    'nodes' => $this->nav_nodes($e, $rules, $host)
                ];
            }
        }
        return $nodes;
    }

    /**
     * 权限选择
     * @param array $rules
     * @return string
     */
    public function selector($rules = null)
    {
        $rules = $rules ?? [];
        $nodes = [];
        foreach ($this->xml->documentElement->childNodes as $e) {
            if (!($e instanceof \DOMElement && $e->tagName == 'nav'))
                continue;
            $auth = $e->getAttribute('auth');
            if ($auth === '')
                $auth = '1';
            if ($auth)
                $nodes[] = $e;
        }
        $s = '<dt>';
        foreach ($nodes as $i => $e) {
            $s .= '<a href="javascript:;"' . ($i === 0 ? ' class="active"' : '') . '>';
            $s .= $e->getAttribute('name');
            $s .= '</a>';
        }
        $s .= '</dt>';
        foreach ($nodes as $e) {
            $s .= '<dd>';
            $s .= '<label><input type="checkbox" onclick="$(this).parent().parent().find(\'input\').prop(\'checked\', $(this).prop(\'checked\'));" /> 全选</label>';
            $s .= $this->selector_nodes($e, $rules, $e->getAttribute('module') ?: '*');
            $s .= '</dd>';
        }
        $s = '<dl class="tabs tabs-card">' . $s . '<dl>';
        return $s;
    }

    private function selector_nodes($element, &$rules, $module)
    {
        $s1 = '';
        foreach ($element->childNodes as $e) {
            if (!($e instanceof \DOMElement && $e->tagName == 'act'))
                continue;
            $v = $module . ':' . $e->parentNode->getAttribute('url') . ':' . $e->getAttribute('value');
            $s1 .= '<label style="margin-right: 10px;">';
            $s1 .= '<input type="checkbox" name="_rules[]" value="' . $v . '"' . (in_array($v, $rules) ? ' checked="checked"' : '') . '/> ';
            $s1 .= $e->getAttribute('name');
            $s1 .= '</label>';
        }
        if ($s1 != '') {
            $s1 = '<span style="padding-left: 20px;">' . $s1 . '</span>';
        }
        $s2 = '';
        foreach ($element->childNodes as $e) {
            if (!($e instanceof \DOMElement && $e->tagName == 'nav'))
                continue;
            $auth = $e->getAttribute('auth');
            if ($auth === '')
                $auth = '1';
            if ($auth) {
                $v = $module . ':' . $e->getAttribute('url') . ($e->hasAttribute('path') ? '/' . $e->getAttribute('path') : '/*') . ':';
                if ($e->getElementsByTagName('act')->length === 0)
                    $v .= ($e->hasAttribute('act') ?: '*');
                $s2 .= '<div>';
                $s2 .= '<label>';
                $s2 .= '<input type="checkbox" name="_rules[]" value="' . $v . '"' . (in_array($v, $rules) ? ' checked="checked"' : '') . '/> ';
                $s2 .= $e->getAttribute('name');
                $s2 .= '</label>';
                $s2 .= $this->selector_nodes($e, $rules, $module);
                $s2 .= '</div>';
            }
        }
        if ($s2 != '') {
            $s2 = '<div style="padding-left: 20px;">' . $s2 . '</div>';
        }
        return $s1 . $s2;
    }

    /**
     * 权限检测
     * @param array $rules
     * @param string $module
     * @param string $path
     * @param string $action
     * @return bool
     */
    public static function privilege($rules, $module = '', $path = '', $action = '')
    {
        if (!$rules)
            return false;
        if (is_null($module) || $module === '')
            $module = '*';
        if (is_null($path) || $path === '') {
            $path = $_SERVER['REQUEST_URI'];
            $path = substr($path, 0, strpos($path . '?', '?'));
        }
        if (is_null($action) || $action === '')
            $action = $_GET['action'] ?? '*';
        $module = strtolower($module);
        $path = '/' . strtolower(trim($path, '/')) . '/';
        $action = strtolower($action);
        foreach ($rules as $rule) {
            list($m, $p, $a) = explode(':', $rule . '::');
            if (!($m === '*' || $m === $module))
                continue;
            if (!($p === '*' || $p === $path || (strpos($p, '*') !== false && strpos($path, trim($p, '*')) === 0)))
                continue;
            if (!($a === '*' || $a === $action))
                continue;
            return true;
        }
        return false;
    }

}
