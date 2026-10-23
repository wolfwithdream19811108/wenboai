<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/19
 * Time: 17:48
 */

namespace plugins\site_analysis\controller;


use cmf\controller\PluginBaseController;
use think\Db;

class AdminIndexController extends PluginBaseController
{
    protected function initialize()
    {
        $adminId = cmf_get_current_admin_id();
        parent::initialize();
        if (!empty($adminId)) {
            if (!$this->checkAccess($adminId)) {
                $this->error("您没有访问权限！");
            }
            $this->assign("admin_id", $adminId);
        } else {
            if ($this->request->isAjax()) {
                $this->error("您还没有登录！", url("admin/Public/login"));
            } else {
                header("Location:" . url("admin/Public/login"));
                exit();
            }
        }
    }

    /**
     * 站点统计
     * @adminMenu(
     *     'name'   => '站点统计',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '站点统计',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        //页面数据
        $siteAnalysisSiteDayDataModel = Db::name('site_analysis_day_data');
        $join = [
            ['__SITE_ANALYSIS_SITE__ site','site.id = day_data.domain_id']
        ];
        $dayDataWhere = $pageWhere = $deviceWhere = [];
        $params = $this->request->param();
        if (empty($params['start_time']) or empty($params['end_time'])){
            $params['start_time'] = date('Y-m-d',time()-7*86400);
            $params['end_time'] = date('Y-m-d');
        }
        $dayDataWhere['day_data.time'] = [['egt',strtotime($params['start_time'])],['elt',strtotime($params['end_time'])]];
        $pageWhere['page.time'] = [['egt',strtotime($params['start_time'])],['elt',strtotime($params['end_time'])]];
        $deviceWhere['device.time'] = [['egt',strtotime($params['start_time'])],['elt',strtotime($params['end_time'])]];
        $areaWhere['area.time'] = [['egt',strtotime($params['start_time'])],['elt',strtotime($params['end_time'])]];
        //站点统计
        $siteData = $siteAnalysisSiteDayDataModel->alias('day_data')->field('sum(day_data.count) as count,sum(day_data.view_count) as view_count,site.domain')->join($join)->where($dayDataWhere)->group('site.domain')->select()->toArray();
        $siteDataMap = [];
        $siteDataMap['legend'] = ['访客数','浏览量'];
        $siteDataMap['category'] = [];
        $siteDataMap['series'] = [];
        foreach ($siteData as $value){
            $siteDataMap['category'][] = $value['domain'];
            $siteDataMap['series']['count']['data'][] = $value['count'];
            $siteDataMap['series']['count']['name'] = '访客数';
            $siteDataMap['series']['view_count']['data'][] = $value['view_count'];
            $siteDataMap['series']['view_count']['name'] = '浏览量';
        }
        //日统计图
        $dayData = $siteAnalysisSiteDayDataModel->alias('day_data')->field('day_data.time,sum(day_data.count) as count,sum(day_data.view_count) as view_count,site.domain')->join($join)->where($dayDataWhere)->group('site.domain,day_data.time')->order('day_data.time asc')->select()->toArray();
        $dayDataMap = [];
        foreach ($dayData as $value){
            $value['time'] = date('Y-m-d',$value['time']);
            $dayDataMap[$value['domain']]['legend'] = ['访客数','浏览量'];
            $dayDataMap[$value['domain']]['category'][] = $value['time'];
            $dayDataMap[$value['domain']]['series']['count']['data'][] = $value['count'];
            $dayDataMap[$value['domain']]['series']['count']['name'] = '访客数';
            $dayDataMap[$value['domain']]['series']['view_count']['data'][] = $value['view_count'];
            $dayDataMap[$value['domain']]['series']['view_count']['name'] = '浏览量';
        }
        //页面访客排行Top5
        $siteAnalysisPageModel = Db::name('site_analysis_page');
        $join = [
            ['__SITE_ANALYSIS_SITE__ site','site.id = page.domain_id']
        ];
        $pages = $siteAnalysisPageModel->alias('page')->field('page.page,page.title,sum(page.count) as count,site.domain')->join($join)->where($pageWhere)->group('site.domain,page.page')->order('count desc')->limit(10)->select()->toArray();
        $pageMap = [];
        foreach ($pages as $value){
            $pageMap[$value['domain']]['visit']['legend'] = ['访客数'];
            $pageMap[$value['domain']]['visit']['category'][] = msubstr($value['title'],0,10);
            $pageMap[$value['domain']]['visit']['series']['data'][] = $value['count'];
            $pageMap[$value['domain']]['visit']['series']['name'] = '访客数';
        }
        $pages = $siteAnalysisPageModel->alias('page')->field('page.page,page.title,sum(page.view_count) as view_count,site.domain')->join($join)->where($pageWhere)->group('site.domain,page.page')->order('view_count desc')->limit(10)->select()->toArray();
        foreach ($pages as $value){
            $pageMap[$value['domain']]['view']['legend'] = ['浏览量'];
            $pageMap[$value['domain']]['view']['category'][] = msubstr($value['title'],0,10);
            $pageMap[$value['domain']]['view']['series']['data'][] = $value['view_count'];
            $pageMap[$value['domain']]['view']['series']['name'] = '浏览量';
        }
        //设备统计
        $siteAnalysisDeviceModel = Db::name('site_analysis_device');
        $join = [
            ['__SITE_ANALYSIS_SITE__ site','site.id = device.domain_id']
        ];
        $devices = $siteAnalysisDeviceModel->alias('device')->field("device.device_type,sum(device.count) as count,sum(device.view_count) as view_count,site.domain")->join($join)->where($deviceWhere)->group('site.domain,device.device_type')->order('view_count desc,count desc')->select()->toArray();
        $deviceMap = [];
        foreach ($devices as $value){
            $deviceMap[$value['domain']]['legend'] = ['访客数','浏览量'];
            $deviceMap[$value['domain']]['category'][] = $value['device_type'];
            $deviceMap[$value['domain']]['series'][0]['data'][] = $value['count'];
            $deviceMap[$value['domain']]['series'][0]['name'] = '访客数';
            $deviceMap[$value['domain']]['series'][1]['data'][] = $value['view_count'];
            $deviceMap[$value['domain']]['series'][1]['name'] = '浏览量';
        }
        $systems = $siteAnalysisDeviceModel->alias('device')->field("device.os_name,sum(device.count) as count,sum(device.view_count) as view_count,site.domain")->join($join)->where($deviceWhere)->group('site.domain,device.os_name')->order('view_count desc,count desc')->select()->toArray();
        $systemsMap = [];
        foreach ($systems as $value){
            $systemsMap[$value['domain']]['legend'] = ['访客数','浏览量'];
            $systemsMap[$value['domain']]['category'][] = $value['os_name'];
            $systemsMap[$value['domain']]['series'][0]['data'][] = $value['count'];
            $systemsMap[$value['domain']]['series'][0]['name'] = '访客数';
            $systemsMap[$value['domain']]['series'][1]['data'][] = $value['view_count'];
            $systemsMap[$value['domain']]['series'][1]['name'] = '浏览量';
        }
        $browsers = $siteAnalysisDeviceModel->alias('device')->field("device.browser,sum(device.count) as count,sum(device.view_count) as view_count,site.domain")->join($join)->where($deviceWhere)->group('site.domain,device.browser')->order('view_count desc,count desc')->select()->toArray();
        $browserMap = [];
        foreach ($browsers as $value){
            $browserMap[$value['domain']]['legend'] = ['访客数','浏览量'];
            $browserMap[$value['domain']]['category'][] = $value['browser'];
            $browserMap[$value['domain']]['series'][0]['data'][] = $value['count'];
            $browserMap[$value['domain']]['series'][0]['name'] = '访客数';
            $browserMap[$value['domain']]['series'][1]['data'][] = $value['view_count'];
            $browserMap[$value['domain']]['series'][1]['name'] = '浏览量';
        }
        //地域统计-国家
        $siteAnalysisAreaModel = Db::name('site_analysis_area');
        $join = [
            ['__SITE_ANALYSIS_SITE__ site','site.id = area.domain_id']
        ];
        $countryDatas = $siteAnalysisAreaModel->alias('area')->field("area.country,sum(area.count) as count,sum(area.view_count) as view_count,site.domain")->join($join)->where($areaWhere)->group('site.domain,area.country')->order('view_count desc,count desc')->select()->toArray();
        $countryMap = [];
        foreach ($countryDatas as $value){
            $countryMap[$value['domain']][] = ['name'=>$value['country'],'count'=>$value['count'],'view_count'=>$value['view_count']];
        }
        $provinceDatas = $siteAnalysisAreaModel->alias('area')->field("area.province,sum(area.count) as count,sum(area.view_count) as view_count,site.domain")->join($join)->where($areaWhere)->where(['country'=>'中国'])->group('site.domain,area.province')->order('view_count desc,count desc')->select()->toArray();
        $provinceMap = [];
        foreach ($provinceDatas as $value){
            $value['province'] = str_replace(['省','市'],'',$value['province']);
            $provinceMap[$value['domain']][] = ['name'=>$value['province'],'count'=>$value['count'],'view_count'=>$value['view_count']];
        }
        $cityDatas = $siteAnalysisAreaModel->alias('area')->field("area.province,area.city,sum(area.count) as count,sum(area.view_count) as view_count,site.domain")->join($join)->where($areaWhere)->group('site.domain,area.city')->order('view_count desc,count desc')->select()->toArray();
        $newCityDatas = [];
        foreach ($cityDatas as $value){
            $newCityDatas[$value['domain']] = empty($newCityDatas[$value['domain']])?[]:$newCityDatas[$value['domain']];
            if (count($newCityDatas[$value['domain']])<10){
                if (empty($value['city'])){
                    $value['city'] = '未知';
                }else{
                    $value['city'] = $value['province'].' '.$value['city'];
                }
                $newCityDatas[$value['domain']][] = $value;
            }
        }
        $this->assign('site_map_data',$siteDataMap);
        $this->assign('day_data_map_data',$dayDataMap);
        $this->assign('page_map_data',$pageMap);
        $this->assign('device_map_data',$deviceMap);
        $this->assign('system_map_data',$systemsMap);
        $this->assign('browser_map_data',$browserMap);
        $this->assign('area_map_data',$countryMap);
        $this->assign('province_map_data',$provinceMap);
        $this->assign('city_data',$newCityDatas);
        $this->assign('start_time',isset($params['start_time'])?$params['start_time']:'');
        $this->assign('end_time',isset($params['end_time'])?$params['end_time']:'');
        return $this->fetch(':admin_index');
    }

    public function page()
    {
        $params = $this->request->param();
        $siteAnalysisPageModel = Db::name('site_analysis_page');
        $join = [
            ['__SITE_ANALYSIS_SITE__ site','site.id = page.domain_id']
        ];
        $where =[];
        if (empty($params['start_time']) or empty($params['end_time'])){
            $params['start_time'] = date('Y-m-d',time()-7*86400);
            $params['end_time'] = date('Y-m-d');
        }
        if (!empty($params['keyword'])){
            $where['page.title|page.page'] = ['like',"%{$params['keyword']}%"];
        }
        $where['page.time'] = [['egt',strtotime($params['start_time'])],['elt',strtotime($params['end_time'])]];
        $pages = $siteAnalysisPageModel->alias('page')->field('page.page,page.title,sum(page.count) as count,sum(page.view_count) as view_count,site.domain')->join($join)->where($where)->group('site.domain,page.page')->order('count desc,page.id')->paginate();
        $this->assign('pages',$pages->items());
        $this->assign('page',$pages->render());
        $this->assign('start_time',isset($params['start_time'])?$params['start_time']:'');
        $this->assign('end_time',isset($params['end_time'])?$params['end_time']:'');
        $this->assign('keyword',isset($params['keyword'])?$params['keyword']:'');
        return $this->fetch(':page');
    }

    /**
     *  检查后台用户访问权限
     * @param int $userId 后台用户id
     * @return boolean 检查通过返回true
     */
    private function checkAccess($userId)
    {
        // 如果用户id是1，则无需判断
        if ($userId == 1) {
            return true;
        }

        $pluginName = $this->request->param('_plugin');
        $controller = $this->request->param('_controller');
        $controller = cmf_parse_name($controller, 1);
        $action     = $this->request->param('_action');

        return cmf_auth_check($userId, "plugin/{$pluginName}/$controller/$action");
    }
}