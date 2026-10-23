<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

$databaseConfigFile = CMF_ROOT . "data/conf/database.php";

// Docker 默认使用本地库；显式设置 CMF_DATABASE_MODE=remote 时使用原远程配置。
if (
    getenv('CMF_ENV') === 'local'
    && getenv('CMF_DATABASE_MODE') !== 'remote'
    && file_exists(CMF_ROOT . "data/conf/database.local.php")
) {
    $databaseConfigFile = CMF_ROOT . "data/conf/database.local.php";
}

if(file_exists($databaseConfigFile)){
    $database=include $databaseConfigFile;
}else{
    $database=[];
}

return $database;
