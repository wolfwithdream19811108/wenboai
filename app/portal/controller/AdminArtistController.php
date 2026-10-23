<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use app\portal\model\PortalTagModel;
use app\portal\model\PortalAgeModel;
use app\portal\model\PortalMuseumModel;
use cmf\controller\AdminBaseController;
use app\portal\model\PortalPostModel;
use app\portal\service\PostService;
use app\portal\model\PortalCategoryModel;
use app\portal\model\PortalCurrentArtistsModel;
use app\portal\model\PortalCurrentWorksModel;
use think\Db;
use app\admin\model\ThemeModel;


class AdminArtistController extends AdminBaseController
{
    /**
     * 文章列表
     * @adminMenu(
     *     'name'   => '文章管理',
     *     'parent' => 'portal/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {

        $content = hook_one('portal_admin_article_index_view');

        if (!empty($content)) {
            return $content;
        }



        
        $category                  = $this->request->param('category', 0, 'strval');
		$currentPage                  = $this->request->param('page', 0, 'intval');
		$page = 20;
		$artistList = array();
		$PortalArtistModel = new PortalCurrentArtistsModel();
		
		
	


		if(empty($artistList)){
			if($category!='全部'){
				$where['category'] = $category;
				$artistList = $PortalArtistModel->field('id,name,category,avatar,artistid,title,province,works_name,honor,birth_date')->where($where)->order('id asc')->group('id');
			}else{

				$artistList = $PortalArtistModel->field('id,name,category,avatar,artistid,title,province,works_name,honor,birth_date')->order('id asc')->group('id');
			}
	
		}           
		$artistList = $artistList->paginate(intval($page));	
		$artistList->appends(request()->get());
		$artistList->appends(request()->post());
		



		$this->assign('artists',$artistList->items());
		$this->assign('page', $artistList->render());


        

        return $this->fetch();
    }

    /**
     * 添加文章
     * @adminMenu(
     *     'name'   => '添加文章',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加文章',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $content = hook_one('portal_admin_article_add_view');

        if (!empty($content)) {
            return $content;
        }

        $themeModel        = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Article/index');
        $this->assign('article_theme_files', $articleThemeFiles);
        return $this->fetch();
    }

    /**
     * 添加文章提交
     * @adminMenu(
     *     'name'   => '添加文章提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加文章提交',
     *     'param'  => ''
     * )
     */
    public function addArtist()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();



           /* $post = $data['post'];*/

/*            $result = $this->validate($post, 'AdminArticle');
            if ($result !== true) {
                $this->error($result);
            }*/

            $portalCurrentArtistsModel = new PortalCurrentArtistsModel();



            $portalCurrentArtistsModel->adminAddArtist($data['artist']);

            $id = $portalCurrentArtistsModel->id;
/*            $hookParam          = [
                'is_add'  => true,
                'article' => $data['post']
            ];
            hook('portal_admin_after_save_article', $hookParam);*/


            $this->success('添加成功!', url('AdminArtist/edit', ['id' => $id]));
        }

    }

    /**
     * 编辑文章
     * @adminMenu(
     *     'name'   => '编辑文章',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑文章',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
           $content = hook_one('portal_admin_article_edit_view');

        if (!empty($content)) {
            return $content;
        }

        $id = $this->request->param('id', 0, 'intval');

        $portalCurrentArtistsModel = new PortalCurrentArtistsModel();
        $artist            = $portalCurrentArtistsModel->where('id', $id)->find();
 

        $themeModel        = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Article/index');		

        $this->assign('article_theme_files', $articleThemeFiles);
        $this->assign('artist', $artist);

		
        return $this->fetch();
    }

    /**
     * 编辑文章提交
     * @adminMenu(
     *     'name'   => '编辑文章提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑文章提交',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     */
    public function editArtist()
    {

        if ($this->request->isPost()) {
            $data = $this->request->param();


            
            $portalCurrentArtistsModel = new PortalCurrentArtistsModel();
            $portalCurrentArtistsModel->adminEditCurrentArtist($data['artist']);

/*            $hookParam = [
                'is_add'  => false,
                'article' => $data['post']
            ];
		
            hook('portal_admin_after_save_article', $hookParam);*/

            $this->success('保存成功!');

        }
    }

    /**
     * 文章删除
     * @adminMenu(
     *     'name'   => '文章删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章删除',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        $param           = $this->request->param();
        $PortalCurrentArtistsModel = new PortalCurrentArtistsModel();

		$userId = cmf_get_current_admin_id();
        if (empty($userId)) {
			$this->error("用户没有登录，请先登录后再操作.");			
        }else{
			if ($userId==1){
				if (isset($param['id'])) {
					$id           = $this->request->param('id', 0, 'intval');
					$result       = $PortalCurrentArtistsModel->where('id', $id)->delete();
					Db::commit();
					$this->success("删除成功！", '');

				}
				
				if (isset($param['ids'])) {
					$ids     = $this->request->param('ids/a');
					$result  = $PortalCurrentArtistsModel->where('id', 'in', $ids)->delete();
					Db::commit();
					$this->success("删除成功！", '');
				}


			}else{

				$this->error("用户没有权限删除，请用正确用户登录后删除.");	
			}
		}
    }

   

    
}
