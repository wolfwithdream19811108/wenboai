<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

$databaseConfigFile = ROOT_PATH . "data/conf/database.php";

// Docker 默认使用本地库；显式设置 CMF_DATABASE_MODE=remote 时使用原远程配置。
if (
    getenv('CMF_ENV') === 'local'
    && getenv('CMF_DATABASE_MODE') !== 'remote'
    && file_exists(ROOT_PATH . "data/conf/database.local.php")
) {
    $databaseConfigFile = ROOT_PATH . "data/conf/database.local.php";
}

if(file_exists($databaseConfigFile)){
    $database=include $databaseConfigFile;
}else{
    $database=[];
}

return $database;
