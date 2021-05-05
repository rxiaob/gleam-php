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

/**
 * 控制器基类
 */
abstract class Controller
{

    protected $app;
    protected $request;

    /**
     * 构造器
     * @access public
     * @param App
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
    }

}
