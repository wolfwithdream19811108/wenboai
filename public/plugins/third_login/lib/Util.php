<?php
// +----------------------------------------------
// | @Author: llr <821860835@qq.com>
// +----------------------------------------------
// | @Date:   2019-06-07 23:44:09
// +----------------------------------------------
namespace plugins\third_login\lib;

/**
 * 工具类
 */

class Util{
	/**
	 * 下载远程文件
	 * @param  string $url  	远程文件url
	 * @param  string $filename 文件保存路径+文件名称
	 * @return void
	 */
	public static function downloadRemoteFile($url = '', $filename = '') 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        $file = curl_exec($ch);
        curl_close($ch);

        $path = dirname($filename);
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $resource = fopen($filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
	}

    /**
     * 删除目录及目录下所有文件或删除指定文件
     * @param str $path   待删除目录路径
     * @param int $delDir 是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
     * @return bool 返回删除状态
     */
    public static function delDirAndFile($path, $del_dir = false) {
        if(!file_exists($path)) return false;
        
        $handle = opendir($path);
        if ($handle) {
            while (false !== ( $item = readdir($handle) )) {
                if ($item != "." && $item != "..") {
                    is_dir("$path/$item") ? self::delDirAndFile("$path/$item", $del_dir) : unlink("$path/$item");
                }
            }
            closedir($handle);
            if ($del_dir) return rmdir($path);
        }else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return false;
            }
        }
    }
}