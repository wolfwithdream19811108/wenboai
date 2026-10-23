<?php

/**
 * Docker 本地数据库配置。
 * 仅在 CMF_ENV=local 时由 app/database.php 和 api/database.php 加载。
 */
return [
    'type'     => 'mysql',
    'hostname' => getenv('DB_HOST') ?: 'db',
    'database' => getenv('DB_DATABASE') ?: 'wenboai_local',
    'username' => getenv('DB_USERNAME') ?: 'wenboai',
    'password' => getenv('DB_PASSWORD') ?: 'wenboai_local',
    'hostport' => getenv('DB_PORT') ?: '3306',
    'charset'  => 'utf8mb4',
    'prefix'   => 'cmf_',
    'authcode' => getenv('CMF_AUTHCODE') ?: 'local-auth-code',
];
