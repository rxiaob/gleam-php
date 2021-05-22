<?php
// 事件定义文件

return [
    'bind' => [],
    'listen' => [
        'AppInit' => [function () {
            include_once __DIR__ . DIRECTORY_SEPARATOR . 'intercept.php';
        }],
    ],
];