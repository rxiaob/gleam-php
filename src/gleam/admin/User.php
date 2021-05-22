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

class User
{

    public $id;
    public $name;
    public $rules;

    /**
     * 构造器
     * @param int $expire 过期时间，单位秒
     */
    public function __construct($expire = 1800)
    {
        $user = session('_admin_user');
        if ($user) {
            $time = session('_admin_time');
            if ($expire > 0 && time() - $time > $expire) {
                session('_admin_user', null);
            } else {
                session('_admin_time', time());
                $this->id = $user['id'];
                $this->name = $user['username'];
                $this->rules = $user['rules'];
            }
        }
    }

    /**
     * 权限检测
     * @param string $module
     * @param string $path
     * @param string $action
     * @return bool
     */
    public function privilege($module = '', $path = '', $action = '')
    {
        return Auth::privilege($this->rules, $module, $path, $action);
    }

    /**
     * 操作日志
     * @param string $title
     * @param int $type
     * @return void
     */
    public function addLog($title = '', $type = 0)
    {
        $url = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['SERVER_NAME'] . (str_replace(':80', '', ':' . $_SERVER['SERVER_PORT'])) . $_SERVER['REQUEST_URI'];
        $content = [];
        if ($_POST)
            $content['POST'] = $_POST;
        db('user_log')->insert([
            'user_id' => $this->id,
            'user_name' => $this->name,
            'type' => $type,
            'title' => $title,
            'url' => substr($url, 0, 200),
            'method' => strtolower($_SERVER['REQUEST_METHOD']),
            'action' => $_GET['action'] ?? '',
            'content' => $content ? json_encode($content) : '',
            'ip' => request()->ip(),
            'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 200),
        ]);
    }

    /**
     * 哈希加盐
     * @param string $str
     * @param string $suffix
     * @return string
     */
    public static function md5Salt($str, $suffix = '')
    {
        return md5($str . $suffix . '2F49klB7c8KTtxvCQWKxJAD2ru5KQOVy');
    }

    /**
     * 后台用户登录
     * @param string $name 账号
     * @param string $pwd 密码
     * @return array|bool
     * @throws \Exception
     */
    public static function login($name, $pwd)
    {
        if (!($name && $pwd))
            err('账号密码不能为空!');
        $pwd = self::md5Salt($pwd, $name);
        $admin = db('user')->field('id,username,role_id,status')->where(['username' => $name, 'password' => $pwd])->find();
        if (!$admin)
            err('账号密码不正确!');
        return self::login_after($admin);
    }

    /**
     * 后台用户登录
     * @param array $admin
     * @return array|bool
     * @throws \Exception
     */
    public static function login_after($admin)
    {
        if (!$admin)
            err('登录失败!');
        if ($admin['status'] !== 1)
            err('账号未启用!');
        if ($admin['role_id'])
            $admin['role_name'] = db('admin_role')->where(['id' => $admin['role_id']])->value('name');
        if ($admin['id'] === 1) {
            $admin['rules'] = ['*:/*:*'];
        } else {
            $admin['rules'] = array_merge(['*:/home/*:*'], self::getRoleAuth($admin['role_id']), self::getUserAuth($admin['id']));
        }
        session('_admin_user', $admin);
        session('_admin_time', time());
    }

    /**
     * 后台用户退出
     * @return void
     */
    public static function logout()
    {
        session('_admin_user', null);
        session('_admin_time', null);
    }

    /**
     * 获取用户权限
     * @param int $id
     * @return array
     */
    public static function getUserAuth($id)
    {
        $list = [];
        $rs = db('user_auth')->field('rule')->where('user_id', $id)->select();
        foreach ($rs as $r) {
            $list[] = $r['rule'];
        }
        return array_unique($list);
    }

    /**
     * 设置用户权限
     * @param int $user_id
     * @param array $data
     * @param int $create_uid
     * @return void
     */
    public static function setUserAuth($user_id, $data, $create_uid = 0)
    {
        db('user_auth')->where('user_id', $user_id)->delete();
        foreach (array_unique((array)$data) as $v) {
            if ($v) {
                db('user_auth')->insert([
                    'user_id' => $user_id,
                    'rule' => $v,
                    'create_uid' => $create_uid,
                ]);
            }
        }
    }

    /**
     * 获取角色权限
     * @param int $id
     * @return array
     */
    public static function getRoleAuth($id)
    {
        $list = [];
        $rs = db('user_auth')->field('rule')->where('role_id', $id)->select();
        foreach ($rs as $r) {
            $list[] = $r['rule'];
        }
        return array_unique($list);
    }

    /**
     * 设置角色权限
     * @param int $role_id
     * @param array $data
     * @param int $create_uid
     * @return void
     */
    public static function setRoleAuth($role_id, $data, $create_uid = 0)
    {
        db('user_auth')->where('role_id', $role_id)->delete();
        foreach (array_unique((array)$data) as $v) {
            if ($v) {
                db('user_auth')->insert([
                    'role_id' => $role_id,
                    'rule' => $v,
                    'create_uid' => $create_uid,
                ]);
            }
        }
    }

}
