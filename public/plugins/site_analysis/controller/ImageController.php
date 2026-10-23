<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/19
 * Time: 12:04
 */

namespace plugins\site_analysis\controller;


use cmf\controller\PluginBaseController;
use plugins\site_analysis\lib\GeoIp2\Database\Reader;
use think\Db;
use think\Log;

class ImageController extends PluginBaseController
{
    public function make()
    {
        $logType = 'analysis';
        Log::init([
            'type'  =>  'File',
            'path'  =>  RUNTIME_PATH.'/log/site_analysis/',
            'apart_level'   =>  [$logType,'exception'],
            'file_size'     =>2097152,
            'max_files'     => 30
        ]);
        Log::record("Start=========Start",$logType);
        $siteAnalysisSiteModel = Db::name('site_analysis_site');
        $siteAnalysisPageModel = Db::name('site_analysis_page');
        $siteAnalysisCookieModel = Db::name('site_analysis_cookie');
        $siteAnalysisDayDataModel = Db::name('site_analysis_day_data');
        $siteAnalysisDeviceModel = Db::name('site_analysis_device');
        $siteAnalysisAreaModel = Db::name('site_analysis_area');
        $params = $this->request->param();
        Log::record('$params:'.var_export($params,true),$logType);
        $token = session('site_analysis_token');
        if (!empty($params['site']) and $params['token']==$token){
            $params['ip'] = empty($params['ip'])?get_client_ip():$params['ip'];
            $todayTime = strtotime(date('Y-m-d'));
            try{
                $siteAnalysisSiteModel->startTrans();
                $siteFind = $siteAnalysisSiteModel->where(['domain'=>$params['site']])->find();
                if (empty($siteFind)){
                    $domainId = $siteAnalysisSiteModel->insert([
                        'domain'=>$params['site'],
                        'time'=>$todayTime,
                    ],false,true);
                }else{
                    $domainId = $siteFind['id'];
                }
                $cookieFind = $siteAnalysisCookieModel->where(['domain_id'=>$domainId,'cookie'=>$params['cookie'],'time'=>$todayTime,'ip'=>$params['ip']])->find();
                $cookieIsNew = false;
                if (empty($cookieFind)){
                    $cookieIsNew = true;
                    $cookieId = $siteAnalysisCookieModel->insert([
                        'domain_id'=>$domainId,
                        'time'=>$todayTime,
                        'cookie'=>$params['cookie'],
                        'ip'=>$params['ip']
                    ],false,true);
                }else{
                    $cookieId = $cookieFind['id'];
                }
                //清除前一天的缓存数据
                cache("site_analysis_page_".($todayTime-86400),null,null,'site_analysis');
                cache("site_analysis_device_".($todayTime-86400),null,null,'site_analysis');
                cache("site_analysis_area_".($todayTime-86400),null,null,'site_analysis');
                $pageCacheKey = "site_analysis_page_{$todayTime}";
                $deviceCacheKey = "site_analysis_device_{$todayTime}";
                $areaCacheKey = "site_analysis_area_{$todayTime}";
                $pages = cache($pageCacheKey,'',null,'site_analysis');
                $devices = cache($deviceCacheKey,'',null,'site_analysis');
                $areas = cache($areaCacheKey,'',null,'site_analysis');
                $pagesKey = md5($params['ip'].$params['cookie']);
                if (empty($pages)){
                    $pages = [];
                }
                if (empty($devices)){
                    $devices = [];
                }
                Log::record('$pagesKey:'.$pagesKey,$logType);
                $pageCountUpdate = false;
                $pageFind = $siteAnalysisPageModel->where(['domain_id'=>$domainId,'page'=>$params['page'],'time'=>$todayTime])->find();
                if (empty($pageFind)){
                    $pageId = $siteAnalysisPageModel->insert([
                        'time'=>$todayTime,
                        'domain_id'=>$domainId,
                        'page'=>$params['page'],
                        'title'=>$params['title']
                    ],false,true);
                    $pageCountUpdate = true;
                }else{
                    $pageId = $pageFind['id'];
                    if (empty($pages[$pagesKey]) || !in_array($params['page'],$pages[$pagesKey])){
                        $pageCountUpdate = true;
                    }
                }
                $dayDataFind = $siteAnalysisDayDataModel->where(['domain_id'=>$domainId,'time'=>$todayTime])->find();
                if (empty($dayDataFind)){
                    $dayDayId = $siteAnalysisDayDataModel->insert([
                        'time'=>$todayTime,
                        'domain_id'=>$domainId,
                    ],false,true);
                }else{
                    $dayDayId = $dayDataFind['id'];
                }
                //写入设备表
                $deviceMd5 = md5($params['device'].$params['os'].$params['browser'].$params['browser_version']);
                Log::record('$deviceMd5:'.$deviceMd5,$logType);
                $deviceFind = $siteAnalysisDeviceModel->where(['domain_id'=>$domainId,'time'=>$todayTime,'device_type'=>$params['device'],'os_name'=>$params['os'],'browser'=>$params['browser'],'browser_version'=>$params['browser_version']])->find();
                $deviceCountUpdate = false;
                if (empty($deviceFind)){
                    $deviceCountUpdate = true;
                    $deviceId = $siteAnalysisDeviceModel->insert([
                        'domain_id'=>$domainId,
                        'time'=>$todayTime,
                        'device_type'=>$params['device'],
                        'os_name'=>$params['os'],
                        'browser'=>$params['browser'],
                        'browser_version'=>$params['browser_version'],
                    ],false,true);
                }else{
                    $deviceId = $deviceFind['id'];
                    if (empty($devices[$pagesKey]) || !in_array($deviceMd5,$devices[$pagesKey])){
                        $deviceCountUpdate = true;
                    }
                }
                $reader = new Reader(dirname(__DIR__).'/lib/GeoLite2-City.mmdb',['zh-CN']);
                $record = $reader->city($params['ip']);
                $country = $record->country->name;
                $province = $record->mostSpecificSubdivision->name;
                $city = $record->city->name;
                if (in_array($country,['台湾'])){
                    $province = $country;
                    $country = '中国';
                }
                $ipArea = ['country'=>$country,'province'=>empty($province)?'':$province,'city'=>empty($city)?'':$city,'district'=>''];
                $ipArea['province'] = empty($ipArea['province'])?'':$ipArea['province'];
                $ipArea['city'] = empty($ipArea['city'])?'':$ipArea['city'];
                $ipArea['district'] = empty($ipArea['district'])?'':$ipArea['district'];
                Log::record("ip【{$params['ip']}】 地理未知：".var_export($ipArea,true));
                $areaMd5 = md5($ipArea['country'].$ipArea['province'].$ipArea['city'].$ipArea['district']);
                $areaFind = $siteAnalysisAreaModel->where(['domain_id'=>$domainId,'time'=>$todayTime,'country'=>$ipArea['country'],'province'=>$ipArea['province'],'city'=>$ipArea['city'],'district'=>$ipArea['district']])->find();
                $areaNew = false;
                if (empty($areaFind)){
                   $areaId = $siteAnalysisAreaModel->insert([
                        'domain_id'=>$domainId,
                        'time'=>$todayTime,
                        'country'=>$ipArea['country'],
                        'province'=>$ipArea['province'],
                        'city'=>$ipArea['city'],
                        'district'=>$ipArea['district']
                    ],false,true);
                    $areaNew = true;
                }else{
                    $areaId = $areaFind['id'];
                    if (empty($areas[$pagesKey]) || !in_array($areaMd5,$areas[$pagesKey])){
                        $areaNew = true;
                    }
                }
                //更新浏览量
                $siteAnalysisSiteModel->where(['id'=>$domainId])->setInc('view_count');
                Log::record("更新站点浏览量",$logType);
                $siteAnalysisCookieModel->where(['id'=>$cookieId])->setInc('view_count');
                Log::record("更新用户浏览量",$logType);
                $siteAnalysisPageModel->where(['id'=>$pageId])->setInc('view_count');
                Log::record("更新页面浏览量",$logType);
                $siteAnalysisDayDataModel->where(['id'=>$dayDayId])->setInc('view_count');
                Log::record("更新站点日浏览量",$logType);
                $siteAnalysisDeviceModel->where(['id'=>$deviceId])->setInc('view_count');
                Log::record("更新设备浏览量",$logType);
                $siteAnalysisAreaModel->where(['id'=>$areaId])->setInc('view_count');
                Log::record("更新地区浏览量",$logType);
                if ($cookieIsNew){
                    $siteAnalysisSiteModel->where(['id'=>$domainId])->setInc('count');
                    Log::record("网站新访客:ip:{$params['ip']},cookie:{$params['cookie']}",$logType);
                }
                if (empty($devices[$pagesKey])||empty($pages[$pagesKey])){
                    $siteAnalysisDayDataModel->where(['id'=>$dayDayId])->setInc('count');
                    Log::record("日新访客:ip:{$params['ip']},cookie:{$params['cookie']}",$logType);
                }
                if ($pageCountUpdate){
                    $siteAnalysisPageModel->where(['id'=>$pageId])->setInc('count');
                    $pages[$pagesKey][] = $params['page'];
                    cache($pageCacheKey,$pages,'','site_analysis');
                    Log::record("页面新访客:ip:{$params['ip']},cookie:{$params['cookie']}",$logType);
                }
                if ($deviceCountUpdate){
                    $siteAnalysisDeviceModel->where(['id'=>$deviceId])->setInc('count');
                    $devices[$pagesKey][] = $deviceMd5;
                    cache($deviceCacheKey,$devices,'','site_analysis');
                    Log::record("设备终端新访客:ip:{$params['ip']},cookie:{$params['cookie']}",$logType);
                }
                if ($areaNew){
                    $siteAnalysisAreaModel->where(['id'=>$areaId])->setInc('count');
                    $areas[$pagesKey][] = $areaMd5;
                    cache($areaCacheKey,$areas,'','site_analysis');
                    Log::record("访客地区:".var_export($ipArea,true),$logType);
                }
                $siteAnalysisSiteModel->commit();
            }catch (\Exception $exception){
                $siteAnalysisSiteModel->rollback();
                Log::record('$exception:'.$exception->getMessage(),'exception');
            }
        }
        try{
            $imageW = 1;
            $imageH = 1;
            $fontSize = 14;
            $angle = 0;
            $fontFile = __DIR__.'/../view/fonts/xuJinRuYingBiXingKai.ttf';
            $text = '站点统计';
            $fontH = imagefontheight($fontSize);
            $y = $imageH-($imageH-$fontH)/2;
            $image = imagecreate($imageW, $imageH);
            imagecolorallocate($image,255,255,255);
            $texts = $this->mb_str_split($text);
            foreach ($texts as $key=>$v){
                $fontColor = imagecolorallocate($image, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));
                imagettftext($image,$fontSize,$angle,$fontSize*($key+1)*1.5,$y,$fontColor,$fontFile,$v);
            }
            ob_start();
            // 输出图像
            imagepng($image);
            $content = ob_get_clean();
            imagedestroy($image);
            Log::record("End=========End",$logType);
            return response($content, 200, ['Content-Length' => strlen($content)])->contentType('image/png');
        }catch (\Exception $exception){
            Log::record('error:'.$exception->getMessage(),'exception');
            Log::record("End=========End",'exception');
        }

    }
    /**
     * @param $str 要分割的字符串
     * @param int $split_length 分割长度
     * @param string $charset 字符编码
     * @return array|array[]|bool|false|string[]
     */
    protected function mb_str_split($str,$split_length=1,$charset="UTF-8"){
        if(func_num_args()==1){
            return preg_split('/(?<!^)(?!$)/u', $str);
        }
        if($split_length<1)return false;
        $len = mb_strlen($str, $charset);
        $arr = array();
        for($i=0;$i<$len;$i+=$split_length){
            $s = mb_substr($str, $i, $split_length, $charset);
            $arr[] = $s;
        }
        return $arr;
    }
}