<?php

/**
 * Docker 本地运行覆盖项。
 */
return [
    'exception_tmpl' => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',
    'show_error_msg' => true,
    'template' => [
        'cmf_default_theme' => 'Acme',
    ],
    'cache' => [
        'type'   => 'File',
        'path'   => CACHE_PATH,
        'prefix' => 'wenboai_local_',
        'expire' => 0,
    ],
    'cookie' => [
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
