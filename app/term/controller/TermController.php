<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\term\controller;
use cmf\controller\HomeBaseController;



class TermController extends HomeBaseController
{
    

    public function user(){
   
       return $this->fetch("/user");
    }
    public function privacy(){
       return $this->fetch("/privacy"); 
    }
    

    
    

}
