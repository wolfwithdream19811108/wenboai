<?php

namespace app\imageai\controller;

use app\imageai\service\AccessService;
use app\user\model\UserPointsModel;
use cmf\controller\HomeBaseController;
use think\Db;

class CreateController extends HomeBaseController
{
    public function index()
    {
        $isLogin = cmf_is_user_login();
        if ($isLogin && AccessService::isApiOnly(cmf_get_current_user_id())) {
            $this->error('此账号仅支持通过 API 创建图片', '/imageai/account/api-keys');
        }
        if ($isLogin) {
            $this->assign('points', (new UserPointsModel())->getUserPoints(cmf_get_current_user_id()));
        }

        $baseModels = [
            ['model_id' => 0, 'model_name' => '优化', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/智能.jpg'],
            ['model_id' => 127, 'model_name' => 'SDXL', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/sdxl.jpg'],
            ['model_id' => 141, 'model_name' => '工美', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/通用.jpg'],
            ['model_id' => 301, 'model_name' => 'MJ5.1', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/mj52.jpg'],
            ['model_id' => 303, 'model_name' => 'MJ6', 'model_pic' => 'https://aiimage.hongmengyishu.com/model_image/mj6.jpg'],
        ];
        $everythingCategory = Db::name('portal_aiimage_everything')->distinct(true)->field('category')->select()->toArray();
        $everythingImages = Db::name('portal_aiimage_everything')->where('open', 1)->select()->toArray();
        $products = Db::name('ai_image_product')->select()->toArray();
        foreach ($products as &$product) {
            $product['infos'] = Db::name('ai_image_product_prompt')->where('product_id', $product['id'])->select()->toArray();
        }
        unset($product);
        $settings = cmf_get_option('function_settings');
        $this->assign('open3d', $settings['open_AI3D'] ?? 0);
        $this->assign('craftModelList', Db::name('portal_aiimage_model')->where(['model_type' => 'craft', 'open' => 1])->select()->toArray());
        $this->assign('chineseloraList', Db::name('portal_aiimage_model')->where(['model_type' => 'chinese', 'open' => 1])->select()->toArray());
        $this->assign('materialloraList', Db::name('portal_aiimage_model')->where(['is_material' => 1, 'open' => 1])->select()->toArray());
        $this->assign('patternmodelList', Db::name('portal_pattern_category')->select()->toArray());
        $this->assign('everythingCategory', $everythingCategory);
        $this->assign('everythingimgArray', $everythingImages);
        $this->assign('productArray', $products);
        $this->assign('base_models', $baseModels);
        $this->assign('isLogin', $isLogin);
        return $this->fetch('/create');
    }
}
