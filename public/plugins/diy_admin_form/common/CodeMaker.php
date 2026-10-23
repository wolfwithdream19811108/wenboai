<?php


namespace plugins\diy_admin_form\common;

use plugins\diy_admin_form\model\PluginCodeAppModel;
use plugins\diy_admin_form\model\PluginCodeFieldsModel;
use plugins\diy_admin_form\model\PluginCodeTablesModel;

class CodeMaker
{
    public function makeCode($target,$appName,$myTableName,$keyName,$tableTag,$exportFlag,$pageFlag){
        //目标任务
        if(count($target)<=0){
            $info = new Info();$info->code = 1;
            $info->message = '没有设定目标生成任务!';
        }
        //开始生成
        $message = "";  $taskCount = 0; $success = 0;
        //生成顶级菜单控制器
        if(in_array(-1,$target)){
            $info = $this->makeMenuControllerFile($appName,$myTableName);
            $message .= '<br>'.$info->message;
            $taskCount += 1; //统计任务数量
            $success += $info->code === 0 ? 1 : 0; //统计成功数量
        }
        //生成控制器
        if(in_array(0,$target)){
            $info = $this->makeControllerFile($appName,$myTableName);
            $message .= '<br>'.$info->message;
            $taskCount += 1; //统计任务数量
            $success += $info->code === 0 ? 1 : 0; //统计成功数量
        }
        //生成数据模型
        if(in_array(1,$target)){
            $info = $this->makeDataModelFile($appName,$myTableName);
            $message .= '<br>'.$info->message;
            $taskCount += 1; //统计任务数量
            $success += $info->code === 0 ? 1 : 0; //统计成功数量
        }
        
        //生成列表数据类(列表控件使用)
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$myTableName);
        foreach($fields as $k=>$v){
            if(trim($v['control_type']) === 'list'){
                $info = $this->makeAdminDbFile($appName,$v);
                $message .= '<br>'.$info->message;
                $taskCount += 1; //统计任务数量
                $success += $info->code === 0 ? 1 : 0; //统计成功数量
            }
        }
        
        //生成模板-列表页
        if(in_array(2,$target)){
            $info = $this->makeAdminHtmlFile($appName,$myTableName,$keyName,
                $tableTag,'index',$exportFlag,$pageFlag);
            $message .= '<br>'.$info->message;
            $taskCount += 1; //统计任务数量
            $success += $info->code === 0 ? 1 : 0; //统计成功数量
        }
        //生成模板-添加页
        if(in_array(3,$target)){
            $info = $this->makeAdminHtmlFile($appName,$myTableName,$keyName,
                $tableTag,'add',$exportFlag,$pageFlag);
            $message .= '<br>'.$info->message;
            $taskCount += 1; //统计任务数量
            $success += $info->code === 0 ? 1 : 0; //统计成功数量
        }
        //生成模板-编辑页
        if(in_array(4,$target)){
            $info = $this->makeAdminHtmlFile($appName,$myTableName,$keyName,
                $tableTag,'edit',$exportFlag,$pageFlag);
            $message .= '<br>'.$info->message;
            $taskCount += 1; //统计任务数量
            $success += $info->code === 0 ? 1 : 0; //统计成功数量
        }
        

        $result  = "本地路径->&nbsp;&nbsp;".CMF_ROOT;
        $result .= $message;
        $result .= "<br><br><span class='text-danger'>生成文件数:".$taskCount."个,成功数:".$success."个.</span>";
        cache('task_msg',$result);//存入本地缓存
    }
    
    //生成控制器文件 AdminTableNameController
    public function makeMenuControllerFile($appName,$tableName){
        $info = new Info();//返回消息
        $appPath = APP_PATH;//D:\phpEnv\www\xxx.com/app/
        //创建Controllor文件夹
        $path = $appPath.$appName.'/controller';
        //把应用名标准名称  abc  -> Abc
        $appName = strtolower($appName);//把应用名变为小写
        $adminAppName = $this->strConvertClass($appName);
        //控制器文件名设置为  AdminAppnameControllor.php
        $file = $path.'/'.'Admin'.$adminAppName."Controller.php";
        $info->message = "<span class='text-danger'>菜单控制器文件--></span><br>"
            .str_replace(CMF_ROOT,'',$file);
        $createDir = $this->makeDir($path);
        if($createDir){
            $content = $this->getMenuControllerText($appName,$adminAppName,$tableName); //生成菜单控制器内容
            $fw = $this->file_write($file,$content);//创建或更新文件
            if($fw === false){
                $info->code=1;$info->message="<span class='text-danger'>无法创建文件:".$file."</span>";
            }
        }else{
            $info->code=1;$info->message="<span class='text-danger'>无法创建目录:".$path."</span>";
        }
        return $info;
    }
    
    //生成控制器文件 AdminTableNameController
    public function makeControllerFile($appName,$tableName){
        $info = new Info();//返回消息
        $appPath = APP_PATH;//D:\phpEnv\www\xxx.com/app/
        //创建Controllor文件夹
        $path = $appPath.$appName.'/controller';
        //把数据表名转换为标准名称  zzh_abc  -> ZzhAbc
        $className = $this->strConvertClass($tableName);
        //控制器文件名设置为  AdminXyzControllor.php
        $file = $path.'/'.'Admin'.$className."Controller.php";
        $info->message = "<span class='text-danger'>控制器文件--></span><br>"
            .str_replace(CMF_ROOT,'',$file);
        $createDir = $this->makeDir($path);
        if($createDir){
            $content = $this->getControllerText($appName,$className,$tableName); //生成控制器内容
            $fw = $this->file_write($file,$content);//创建或更新文件
            if($fw === false){
                $info->code=1;$info->message="<span class='text-danger'>无法创建文件:".$file."</span>";
            }
        }else{
            $info->code=1;$info->message="<span class='text-danger'>无法创建目录:".$path."</span>";
        }
        return $info;
    }

    //生成数据模型文件  app/model/TableNameModel.php
    public function makeDataModelFile($appName,$tableName){
        $info = new Info();//返回消息
        $appPath = APP_PATH;//D:\phpEnv\www\xxx.com/app/
        //创建model文件夹
        $path = $appPath.$appName.'/model';
        //把数据表名转换为数据模型名称  zzh_abc  -> ZzhAbcModel.php
        $className = $this->strConvertClass($tableName);
        $file = $path.'/'.$className."Model.php";
        $info->message = "<span class='text-primary'>数据模型文件--></span><br>"
            .str_replace(CMF_ROOT,'',$file);
        $createDir = $this->makeDir($path);
        if($createDir){
            $content = $this->getModelText($appName,$className,$tableName); //生成模型内容
            $fw = $this->file_write($file,$content);//创建或更新文件
            if($fw === false){
                $info->code=1;$info->message="<span class='text-danger'>无法创建文件:".$file."</span>";
            }
        }else{
            $info->code=1;$info->message="<span class='text-danger'>无法创建目录:".$path."</span>";
        }
       return $info;
    }

    //生成html后台模板文件
    public function makeAdminHtmlFile($appName,$tableName,$priKeyName,$tableTag,$html,$exportFlag,$pageFlag){
        $info = new Info();//返回消息
        $cmfRoot = CMF_ROOT;//D:\phpEnv\www\codehelper.com/
        $adminTheme = Common::getAdminTheme();//获取后台模板名称
        $adminThemePath = $cmfRoot.'public/themes/'.$adminTheme.'/';//后台模板路径
        //创建模板文件夹,把表名称转换为文件夹名称  test_data  ->  admin_test_data
        $dirName = "admin_".strtolower($tableName);
        $dirName = str_replace("__","_",$dirName);//
        $path = $adminThemePath.$appName.'/'.$dirName;
        //html模板文件名设置为  index.html add.html  edit.html
        $file = $path."/".$html.".html";
        $info->message = "<span class='text-success'>HTML模板文件--></span><br>"
            .str_replace(CMF_ROOT,'',$file);
        $createDir = $this->makeDir($path);
        if($createDir){
            //生成控制器名称 TestData ->  AdminTestData
            $controllerName = "Admin".$this->strConvertClass($tableName);
            $content = $this->getHtmlText($appName,$tableName,$html,$controllerName,$priKeyName,$tableTag,$exportFlag,$pageFlag); //生成html模板内容
            $fw = $this->file_write($file,$content);//创建或更新文件
            if($fw === false){
                $info->code=1;$info->message="<span class='text-danger'>无法创建文件:".$file."</span>";
            }
        }else{
            $info->code=1;$info->message="<span class='text-danger'>无法创建目录:".$path."</span>";
        }
        return $info;
    }

    //生成列表控件数据类文件
    private function getDbfileText($appName,$className,$jsonDb,$tableDb,$fieldTag,$defaultVal){
        $cmfRoot = CMF_ROOT;//D:\phpEnv\www\xxx.com/
        $file = $cmfRoot.'public/plugins/diy_admin_form/common/modelfile/MyDb.php';
        $content = file_get_contents($file);// $this->file_getLines($file);
        if(empty($content) || $content ===false){
            return "模板文件不存在!".$file;
        }

        //替换模板内容
        //替换命名空间  //{NAMESPACE} =>  namespace app\abc\common;
        $content = str_replace('//{NAMESPACE}','namespace app\\'.$appName.'\\common;',$content);
        //替换类名称  //class MyDb -> class DbAbc
        $content = str_replace('class MyDb',"class $className",$content);
        //替换自定义的json数据字符串
        $content = str_replace('//{DBLIST}',$jsonDb,$content); //{DBLIST}
        //替换定义的table数据字符串
        $content = str_replace('//{TABLEDATA}',$tableDb,$content); //{DBLIST}
        //替换首条列表项名称 所有XXX
        $content = str_replace('{TITLE}',$fieldTag,$content);
        //替换首条列表项ID值  {DEFAULT}
        $content = str_replace('{DEFAULT}',$defaultVal,$content);
        
        return $content;
    }

    //生成html模板文件的内容,读取模板文件内容,再替换部分内容
    private function getHtmlText($appName,$tableName,$html,$ctrName,$priKey,$tableTag,$exportFlag,$pageFlag){
        $cmfRoot = CMF_ROOT;//D:\phpEnv\www\xxx.com/
        $file = $cmfRoot.'public/plugins/diy_admin_form/common/modelfile/'.$html.'.html';
        $content = file_get_contents($file);//$this->file_getLines($file);
        if(empty($content) || $content === false){
            return "html文件不存在!".$file;
        }
        
        //替换html模板内容
        //替换URL  {:url('AdminStudy/edit',array('id'=>$vo['id']))}
        $adminIndexController   = "{:url('".$ctrName."/index')}";//index
        $adminAddController     = "{:url('".$ctrName."/add')}";  //add
        $adminAddPostCtrl       = "{:url('".$ctrName."/addPost')}";  //addpost
        $adminEditController    = "{:url('".$ctrName."/edit',array('id'=>\$vo['".$priKey."']))}"; //edit
        $adminEditPostCtrl="{:url('".$ctrName."/editPost',array('id'=>\$post['".$priKey."']))}";//editpost
        $adminExportController  = "{:url('".$ctrName."/export')}"; //export
        $adminRowDelController  = "{:url('".$ctrName."/deleteRows')}"; //批量删除数据行
    
        $content = str_replace('{ADMIN_ADD}',$adminAddController,$content);  //添加页方法
        $content = str_replace('{ADMIN_ADDPOST}',$adminAddPostCtrl,$content);  //添加提交方法
        $content = str_replace('{ADMIN_EDIT}',$adminEditController,$content);  //编辑页方法
        $content = str_replace('{ADMIN_EDITPOST}',$adminEditPostCtrl,$content);//编辑页提交方法
        $content = str_replace('{ADMIN_INDEX}',$adminIndexController,$content);//列表页方法
        $content = str_replace('{ROW_ID}' ,"{\$vo.".$priKey."}",$content);//列表页行id
    
        $content = str_replace('{BATCH_SETFIELD}'
            ,$this->getBatchSetFieldText($tableName,$ctrName),$content);//批量置0/1方法
        $content = str_replace('{BATCH_DELETE}'
            ,$this->getBatchDeleteText($tableName,$adminRowDelController),$content);//批量删除方法
        $content = str_replace('{LIST_EDITDATA}'
            ,$this->getHtmlEditListText($tableName),$content);//编辑页:字段信息
        $content = str_replace('{LIST_ADDDATA}'
            ,$this->getHtmlAddListText($tableName),$content);//添加页:字段信息
    
        if(strpos($content,'{ADMIN_EXPORT}') !== false){
            if(intval($exportFlag) > 0){
                //数据导出按钮
                $content = str_replace('{ADMIN_EXPORT}',$adminExportController,$content);
            }else{
                $content = str_replace('{ADMIN_EXPORT}','#',$content);;
            }
        }
    
        //替换导航文本
        $content = str_replace('{ITEM_ALL}','所有'.$tableTag,$content);
        $content = str_replace('{ITEM_ADD}','添加'.$tableTag,$content);
        $content = str_replace('{ITEM_EDIT}',"编辑".$tableTag,$content);
        $content = str_replace('{ITEM_DEL}',"删除",$content);
        $content = str_replace('{BATCH_SETFIELD0}',"启用",$content);//批量置0
        $content = str_replace('{BATCH_SETFIELD1}',"禁用",$content);//批量置1
        //$content = str_replace('{BATCH_SETDELETE}',"删除",$content);//批量删除
    
        //列表页:查询/列表/汇总
        if($html === 'index'){
            //顶部查询部分
            $content = str_replace('{INDEX_QUERY}',
                $this->getIndexQueryCode($appName,$tableName,$pageFlag),$content);
            //中部列表部分,确定显示哪些字段
            $content = str_replace('{//INDEX_LIST_HEAD}',
                $this->getIndexListThTdCode($tableName,'th'),$content);
            $content = str_replace('{//INDEX_LIST_DATA}',
                $this->getIndexListThTdCode($tableName,'td'),$content);
            //底部汇总部分 {//INDEX_COUNT}
            $content = str_replace('{//INDEX_COUNT}',
                $this->getIndexCountCode($tableName),$content);
        }
        
        return $content;
    }

    //生成添加页面代码
    private function getHtmlAddListText($tableName){
        $pcfm   = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        $code = "";$flag = 0;
        $tab = "\t\t\t\t\t";
        foreach ($fields as $k=>$f){
            if(intval($f['used_add']) > 0){
                $name  = trim($f['field_name']);
                $tag   = trim($f['field_tag']);
                $ctrtype= trim($f['control_type']);
                $checkv1= intval($f['ctr_check_v1']);//check选中状态的值
                $maxlen= Common::getFieldLength($tableName,$name);//获取指定字符型字段的最大长度
                $maxStr= $maxlen > 0 ? ' maxlength="'.$maxlen.'"' : '';
                $val   = "";//添加页面不设置默认值
                $code .= ($flag>0 ? "\t\t\t\t\t":"")."<tr>".PHP_EOL;
                $code .= $tab."\t".'<th'.($flag<=0 ? ' width="150"':'').'>'.$tag
                    .'<span class="form-required">*</span></th>'.PHP_EOL;
                $code .= $tab."\t"."<td>".PHP_EOL;
                //根据字段类型不同使用不同的控件,与查询使用的控件一致,默认使用普通文本框
                $code .= $this->_getControlText($name,$ctrtype,$maxStr,$tag,$val,$checkv1);
                $code .= $tab."\t"."</td>".PHP_EOL;
                $code .= $tab."</tr>".PHP_EOL;
                $flag += 1;
            }
        }
        return $code;
    }

    //生成批量删除按钮代码
    private function getHtmlEditListText($tableName){
        $pcfm   = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        $code = "";$flag = 0;
        $tab = "\t\t\t\t\t";
        foreach ($fields as $k=>$f){
            if(intval($f['used_edit']) > 0){
                $name  = trim($f['field_name']);
                $tag   = trim($f['field_tag']);
                $ctrtype= trim($f['control_type']);
                $checkv1= intval($f['ctr_check_v1']);//check选中状态的值
                $maxlen= Common::getFieldLength($tableName,$name);//获取指定字符型字段的最大长度
                $maxStr= $maxlen > 0 ? ' maxlength="'.$maxlen.'"' : '';
                $val   = "{\$post.".$name."|default=''}";//编辑页面设置值
                $code .= ($flag>0 ? "\t\t\t\t\t":"")."<tr>".PHP_EOL;
                $code .= $tab."\t".'<th'.($flag<=0 ? ' width="150"':'').'>'.$tag
                    .'<span class="form-required">*</span></th>'.PHP_EOL;
                $code .= $tab."\t"."<td>".PHP_EOL;
                //根据字段类型不同使用不同的控件,与查询使用的控件一致,默认使用普通文本框

                $code .= $this->_getControlText($name,$ctrtype,$maxStr,$tag,$val,$checkv1);
                $code .= $tab."\t"."</td>".PHP_EOL;
                $code .= $tab."</tr>".PHP_EOL;
                $flag += 1;
            }
        }
        return $code;
    }
    private function _getControlText($name,$ctrtype,$maxStr,$tag,$val,$checkv1){
        $tree = "{\$".$name."_tree|default=''}";//引用字符 {$abc_tree|default=''}
        $code = "";
        switch($ctrtype){
            case 'text-year'://年度选择文本框
                $code = <<<CODE
\t\t\t\t\t\t\t<input class="form-control js-bootstrap-year" type="text" name="$name"
                                   required value="$val" $maxStr
                                   placeholder="请输入$tag"/>
CODE;
                break;
            case 'text-date':
                $code = <<<CODE
\t\t\t\t\t\t\t<input class="form-control js-bootstrap-date" type="text" name="$name"
                                   required value="$val" $maxStr
                                   placeholder="请输入$tag"/>
CODE;
                break;
            case 'text-datetime':
                $code = <<<CODE
\t\t\t\t\t\t\t<input class="form-control js-bootstrap-datetime" type="text" name="$name"
                                   required value="$val" $maxStr
                                   placeholder="请输入$tag"/>
CODE;
                break;
            case 'list':
                $code = <<<CODE
\t\t\t\t\t\t\t<select title="请选择$tag" class="form-control" name="$name">
\t\t\t\t\t\t\t\t$tree
\t\t\t\t\t\t\t</select>
CODE;
                break;
            case 'check':
                $code = <<<CODE
\t\t\t\t\t\t\t<div class="checkbox" style="margin-bottom: 3px">
\t\t\t\t\t\t\t\t<label class="checkbox-inline" title="请确认{$tag}">
\t\t\t\t\t\t\t\t\t<input type="checkbox" id="$name" name="$name"
\t\t\t\t\t\t\t\t\t\t\t value="$checkv1" {\$chk_$name|default=""}>确认{$tag}
\t\t\t\t\t\t\t\t</label>
\t\t\t\t\t\t\t</div>
CODE;
                break;
                //默认全部按普通文本框处理
            default:
                $code .= <<<TD
\t\t\t\t\t\t\t<input class="form-control" type="text" name="$name"
                                   required value="$val" $maxStr
                                   placeholder="请输入$tag"/>
TD;

        }
        return $code.PHP_EOL;
    }

    //生成批量删除按钮代码
    private function getBatchSetFieldText($tableName,$ctrName){
        $pctm = new PluginCodeTablesModel();
        $row = $pctm->getRowByTableName(Common::getDbPrefix().$tableName);
        $batchSetfield = isset($row['batch_setfield']) ? trim($row['batch_setfield']) : '';
        if(strlen($batchSetfield)<=0){
            return "";
        }
        //字段名生成方法名  filed_name ->  {:url('AdminXyz/setFieldName',array('f'=>0))}
        $adminUrl0 = "{:url('".$ctrName."/set".$this->strConvertClass($batchSetfield)."',array('f'=>0))}";
        $adminUrl1 = "{:url('".$ctrName."/set".$this->strConvertClass($batchSetfield)."',array('f'=>1))}";
        $code = <<<CODE
<button class="btn btn-success btn-xs js-ajax-submit" type="submit"
                    data-action="$adminUrl0"
                    data-subcheck="true" data-msg="您确定启用吗？">启用
            </button>
            <button class="btn btn-warning btn-xs js-ajax-submit" type="submit"
                    data-action="$adminUrl1"
                    data-subcheck="true" data-msg="您确定禁用吗？">禁用
            </button>
CODE;

        return $code;
    }
    //生成批量删除按钮代码
    private function getBatchDeleteText($tableName,$adminRowDelController){
        $pctm = new PluginCodeTablesModel();
        $row = $pctm->getRowByTableName(Common::getDbPrefix().$tableName);
        $batchDel = isset($row['batch_delete']) ? intval($row['batch_delete']) : 0;
        $idField  = isset($row['key_name']) ? trim($row['key_name']) : '';
        if($batchDel<=0 || strlen($idField)<=0){
            return "";
        }

        $code = <<<CODE
<button class="btn btn-danger btn-xs js-ajax-submit" type="submit"
                    data-action="$adminRowDelController" data-subcheck="true"
                    data-msg="确定删除吗？">删除
            </button>
CODE;

        return $code;
    }
    //生成模型文件的内容,读取模板文件内容,再替换部分内容
    private function getModelText($appName,$className,$tableName){
        $cmfRoot = CMF_ROOT;//D:\phpEnv\www\xxx.com/
        $file = $cmfRoot.'public/plugins/diy_admin_form/common/modelfile/MyModel.php';
        $content = file_get_contents($file) ;$this->file_getLines($file);
        if(empty($content) || $content===false){
            return "模板文件不存在!".$file;
        }
        //替换命名空间  //{NAMESPACE} =>  namespace app\abc\model;
        $content = str_replace('//{NAMESPACE}','namespace app\\'.$appName.'\\model;',$content);
        //替换类名称  MyModel  ->  XyzModel
        $content = str_replace('MyModel',$className.'Model',$content);
        //替换关键字段名称
        $content = str_replace('{KEYNAME}', $this->_getTableKeyName($tableName), $content);
        //替换sql查询
        $content = str_replace('//{SWITCH}', $this->getModelQueryCode($tableName), $content);
       return $content;
    }
    //替换模型文件sql查询
    private function getModelQueryCode($tableName){
        $switch = <<<MM
switch (\$name){
ITEMS
                default:
            }
MM;
        $switchItem = <<<ITEM
\t\t\t\tcase 'FIELDNAME': 
\t\t\t\tSQL_EXPRESS
                    break;
ITEM;

        $items = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        foreach ($fields as $k=>$v){
            if(strlen($v['control_type'])>0){
                $fieldname = trim($v['field_name']);
                $compare   = trim($v['compare_type']);
                $minVal    = intval($v['ctr_min_val']);//最小有效值
                $exp   = $this->getModelQueryExpress($fieldname,$compare,$minVal);
                $case  = str_replace("FIELDNAME",$fieldname,$switchItem);
                $case  = str_replace('SQL_EXPRESS',$exp,$case);
                $items.= $case.PHP_EOL;
            }
        }
        return str_replace("ITEMS",$items,$switch);
    }
    private function getModelQueryExpress($name,$compare,$minVal){
        switch ($compare){
            case 'text-like':
                $s=<<<PP
if(strlen(\$value) > 0){
                    \$db->whereLike('a.$name',"%\$value%");
                }
PP;
                break;
            case 'text-like-right':
                $s=<<<PP
 if(strlen(\$value) > 0){
                    \$db->whereLike('a.$name',"\$value%");
                }
PP;
                break;
            case 'text-like-left':
                $s=<<<PP
if(strlen(\$value) > 0){
                    \$db->whereLike('a.$name',"%\$value");
                }
PP;
                break;
            case 'text-equal':
                $s=<<<PP
if(strlen(\$value) > 0){
                    \$db->where('a.$name',"\$value");
                }
PP;
                break;
            case 'text-notequal':
                $s=<<<PP
if(strlen(\$value) > 0){
                    \$db->where('a.$name','<>',"\$value");
                }
PP;
                break;
            case 'number-equal':
                $s=<<<PP
 if(\$value !== '' && \$value >= $minVal){
                    \$db->where('a.$name',\$value);
                }
PP;
                break;
            case 'number-notequal':
                $s=<<<PP
if(\$value !== '' && \$value >= $minVal){
                    \$db->where('a.$name','<>',\$value);
                }
PP;
                break;
            case 'number-gt':
                $s=<<<PP
if(\$value !== '' && \$value >= $minVal){
                    \$db->where('a.$name','>',\$value);
                }
PP;
                break;
            case 'number-egt':
                $s=<<<PP
if(\$value !== '' && \$value >= $minVal){
                    \$db->where('a.$name','>=',\$value);
                }
PP;
                break;
            case 'number-lt':
                $s=<<<PP
 if(\$value !== '' && \$value >= $minVal){
                    \$db->where('a.$name','<',\$value);
                }
PP;
                break;
            case 'number-elt':
                $s=<<<PP
 if(\$value !== '' && \$value >= $minVal){
                    \$db->where('a.$name','<=',\$value);
                }
PP;
                break;
            case 'datetime-equal':
                $s=<<<PP
if(\$value !== ''){
                    \$db->where('a.$name',"\$value");
                }
PP;
                break;
            case 'datetime-notequal':
                $s=<<<PP
if(\$value !== ''){
                    \$db->where('a.$name','<>',"\$value");
                }
PP;
                break;
            default:
                $s="";
        }
        return $s;
    }
    
    
    //生成控制器文件的内容,读取控制器模板文件内容,再替换部分内容
    private function getMenuControllerText($appName,$className,$tableName){
        $cmfRoot = CMF_ROOT;//D:\phpEnv\www\xxx.com/
        $file = $cmfRoot.'public/plugins/diy_admin_form/common/modelfile/AdminIndexController.php';
        $content = file_get_contents($file);//$this->file_getLines($file);
        if(empty($content) || $content === false){
            return "模板文件不存在!".$file;
        }
        $pctm = new PluginCodeTablesModel();
        $tableConfig = $pctm->getRowByTableName(Common::getDbPrefix().$tableName);
        
        //替换命名空间  //{NAMESPACE} =>  namespace app\abc\model;
        $content = str_replace('//{NAMESPACE}', 'namespace app\\'.$appName.'\\controller;', $content);
        //应用名称
        $content = str_replace('APPNAME', $appName, $content);
        //替换类名称  AdminXyzController
        $content = str_replace('AdminIndexController', 'Admin'.$className.'Controller', $content);
        
        //顶级菜单名称
        //$topMenuName = isset($tableConfig['top_menu_name']) ? trim($tableConfig['top_menu_name']) : '我的应用菜单';
        $app = $this->getAppRow($tableConfig['app_id']);
        $content = str_replace('MAIN_MENU_NAME',$app['menu_name'],$content);
        $content = str_replace('MAIN_MENU_ORDER',$app['menu_order'],$content);
        $content = str_replace('MAIN_MENU_ICON',$app['menu_icon'],$content);
        
        return $content;
    }
    
    private function getAppRow($appId){
        $pcam = new PluginCodeAppModel();
        return $pcam->getRowById(intval($appId));
    }
    
    //生成控制器文件的内容,读取控制器模板文件内容,再替换部分内容
    private function getControllerText($appName,$className,$tableName){
        $cmfRoot = CMF_ROOT;//D:\phpEnv\www\xxx.com/
        $file = $cmfRoot.'public/plugins/diy_admin_form/common/modelfile/AdminMyController.php';
        $content = file_get_contents($file);//$this->file_getLines($file);
        if(empty($content) || $content === false){
            return "模板文件不存在!".$file;
        }
        $pctm = new PluginCodeTablesModel();
        $tableConfig = $pctm->getRowByTableName(Common::getDbPrefix().$tableName);
        $batchSetField = isset($tableConfig['batch_setfield']) ? trim($tableConfig['batch_setfield']) : "undefined";
        $useInfo = [];//控制器引用的类
    
        //替换命名空间  //{NAMESPACE} =>  namespace app\abc\model;
        $content = str_replace('//{NAMESPACE}', 'namespace app\\'.$appName.'\\controller;', $content);
        //应用名称
        $content = str_replace('APPNAME', $appName, $content);
        //替换类名称  AdminXyzController
        $content = str_replace('AdminMyController', 'Admin'.$className.'Controller', $content);
        //替换MyModel();为表模型名,例如TestStudentModel();并加入引用
        $modelName = $this->strConvertClass($tableName,'Model');//table -> TableModel
        $content = str_replace('MyModel()',$modelName."()", $content);
        //加入表模型引用
        $content = str_replace('//{USEMODEL}',"use app\\".$appName."\model\\$modelName;", $content);
        //替换列表页使用的字段信息
        $content = str_replace('//{CREATEFIELDS}', $this->_getAdminIndexFieldsCode($tableName, $appName, $useInfo), $content);
        //生成汇总字段代码
        // $this->assign('total',$mydatamodel->getDataList($fields,'id',0,'sum','score'));
        $content = str_replace('//{SUMMARY}', $this->_getSummaryCode($tableName), $content);
    
        //替换关键字段名称
        $content = str_replace('{KEYNAME}', $this->_getTableKeyName($tableName), $content);
        //引用数据文件
        $content = str_replace('//{USECOMMON_DB}',$this->arr2line($useInfo),$content);
        //可以批量设置值的字段名称
        $content = str_replace('SETFIELD',$batchSetField,$content);
        //填充列表项(编辑页)//{FILL_LISTITEMS}
        $content = str_replace('//{FILL_LISTITEMS}',$this->getListItemsCode($tableName),$content);
        //填充复选框(编辑页)//{FILL_CHECKITEMS}
        $content = str_replace('//{FILL_CHECKITEMS}',$this->getCheckItemsCode($tableName),$content);
        //填充列表项(添加页)//{FILL_LISTITEMS0}
        $content = str_replace('//{FILL_LISTITEMS0}',$this->getListItemsCode($tableName,'add'),$content);
        //填充复选框(添加页)//{FILL_CHECKITEMS0}
        $content = str_replace('//{FILL_CHECKITEMS0}',$this->getCheckItemsCode($tableName,'add'),$content);
        //替换控制器名
        $content = str_replace('{ADMIN_NAME}','Admin'.$className,$content);
        //替换上级菜单控制器名
        $app_name = strtolower($appName);
        $content = str_replace('{ADMIN_INDEX_NAME}'
            ,'Admin'.$this->strConvertClass($app_name),$content);
        //添加数据的字段列表
        $content = str_replace('//{ADD_FIELDS}',$this->getFieldsData($tableName,'add'),$content);
        //编辑数据的字段列表
        $content = str_replace('//{EDIT_FIELDS}',$this->getFieldsData($tableName,'edit'),$content);
        $menuInfo = $this->getMenuInfo($tableName);
        //菜单设置 名称
        $content = str_replace('MENU_NAME',$menuInfo['menu_name'],$content);
//        //菜单设置 上级
//        $content = str_replace('MENU_PARENT',$menuInfo['menu_parent_url'],$content);
//        //菜单设置 序号
//        $content = str_replace('MENU_ORDER',$menuInfo['menu_order'],$content);
//        //菜单设置 名称
//        $content = str_replace('MENU_ICON',$menuInfo['menu_icon'],$content);
        
        
        return $content;
    }
    
    //获取菜单配置
    private function getMenuInfo($tableName){
        $pctm = new PluginCodeTablesModel();
        $row = $pctm->getRowByTableName(Common::getDbPrefix().$tableName);
        $menu = [
            'menu_name'         => isset($row['menu_name']) ? trim($row['menu_name']) : '',
            'menu_parent'       => isset($row['menu_parent']) ? trim($row['menu_parent']) : '',
            'menu_order'        => isset($row['menu_order']) ? intval($row['menu_order']) : 100,
            'menu_icon'         => isset($row['menu_icon']) ? trim($row['menu_icon']) : '',
            'menu_parent_url'   => isset($row['menu_parent_url']) ? trim($row['menu_parent_url']) : '',
        ];
        return $menu;
    }
    
    //添加或编辑所使用的字段数据
    private function getFieldsData($tableName,$type='add'){
        $m = <<<CODE
\t\t\t\$data['FIELDNAME']\t\t= isset(\$params['FIELDNAME']) ? \$params['FIELDNAME'] : DEFAULT;
CODE;
        $addCode  = "";
        $editCode = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        foreach($fields as $k=>$v){
            $fieldName = trim($v['field_name']);
            $controlType = trim($v['control_type']);
            $controlDefault = trim($v['control_default']);
            $default = $this->_getControlDefaultValue($controlType,$controlDefault);
            if(intval($v['used_add']) > 0){
                $mm = str_replace("DEFAULT",$default,$m);
                $addCode  .= str_replace("FIELDNAME",$fieldName,$mm).PHP_EOL;
            }
            if(intval($v['used_edit']) > 0){
                $editCode .= str_replace("FIELDNAME",$fieldName,$mm).PHP_EOL;
            }
        }
        
        if($type === 'add'){
            return $addCode;
        }else{
            return $editCode;
        }
    }
    private function _getControlDefaultValue($controlType,$controlDefault){
        if($controlType == 'check'){
            $default = intval($controlDefault);
        }elseif($controlType == 'list'){
            $default = "'".$controlDefault."'";
        }else{
            //$default = "'".$controlDefault."'";
            $default = "''";
        }
        return $default;
    }
    //生成复选框赋值代码
    private function getCheckItemsCode($tableName,$default=''){
        $default = trim($default);
        if(strlen($default)>0){
            //add page
            $m = <<<CODE
\t\t//\$this->assign('chk_FIELDNAME','checked');//设置复选框默认为选中状态
CODE;
        }else{
            //edit page
            $m = <<<CODE
\t\t\$this->assign('chk_FIELDNAME',intval(\$row['FIELDNAME']) === CHECKV1 ? 'checked':'');
CODE;
        }
        
        $code = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        foreach($fields as $k=>$v){
            $fieldName = trim($v['field_name']);
            $checkv1   = intval($v['ctr_check_v1']);
            if(trim($v['control_type']) == 'check'){
                $m = str_replace("CHECKV1",$checkv1,$m);
                $code .= str_replace("FIELDNAME",$fieldName,$m).PHP_EOL;
            }
        }
        return $code;
    }
    
    //生成列表项填充内容代码
    private function getListItemsCode($tableName,$default=''){
        $default = trim($default);
        if(strlen($default)>0){
            //add添加页
            $m = <<<CODE
\t\t\$this->assign("FIELDNAME_tree",MyDb::getListTree('DEFAULT',false));
CODE;
        }else{
            //index列表页
            $m  = <<<CODE
\t\t\$this->assign("FIELDNAME_tree",MyDb::getListTree(\$row['FIELDNAME'],false));
CODE;
        }
        
        $code = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        foreach($fields as $k=>$v){
            $fieldName = trim($v['field_name']);
            $defaultVal= trim($v['ctr_min_val']);
            $modelName = "Db".$this->strConvertClass($fieldName); //field_name -> DbFieldName
            if(trim($v['control_type']) == 'list'){
                $m1= str_replace("FIELDNAME",$fieldName,$m);
                $m1= str_replace("DEFAULT",$defaultVal,$m1);
                $code .= str_replace("MyDb::",$modelName."::",$m1).PHP_EOL;
            }
        }
        return $code;
    }
    //生成汇总字段
    private function _getSummaryCode($tableName){
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        $code = "";
        foreach($fields as $k=>$v){
            if(intval($v['used_count_sum'])>0){
                $fieldName = trim($v['field_name']);
                $code .= "\$this->assign('sum_$fieldName',\$mydatamodel->getDataList(\$fields,";
                $code .= "'".$this->_getTableKeyName($tableName)."',0,'sum','$fieldName'));".PHP_EOL;
            }
            if(intval($v['used_count_avg'])>0){
                $fieldName = trim($v['field_name']);
                $code .= "\$this->assign('avg_$fieldName',\$mydatamodel->getDataList(\$fields,";
                $code .= "'".$this->_getTableKeyName($tableName)."',0,'avg','$fieldName'));".PHP_EOL;
            }
            if(intval($v['used_count_max'])>0){
                $fieldName = trim($v['field_name']);
                $code .= "\$this->assign('max_$fieldName',\$mydatamodel->getDataList(\$fields,";
                $code .= "'".$this->_getTableKeyName($tableName)."',0,'max','$fieldName'));".PHP_EOL;
            }
            if(intval($v['used_count_min'])>0){
                $fieldName = trim($v['field_name']);
                $code .= "\$this->assign('min_$fieldName',\$mydatamodel->getDataList(\$fields,";
                $code .= "'".$this->_getTableKeyName($tableName)."',0,'min','$fieldName'));".PHP_EOL;
            }
        }
        return $code;
    }
    //获取数据表关键字段名称
    private function _getTableKeyName($tableName){
        $tableName = Common::getDbPrefix().$tableName;
        $pctm = new PluginCodeTablesModel();
        $row = $pctm->getRowByTableName($tableName);
        return isset($row['key_name']) ? trim($row['key_name']) : 'id';
    }
    
    //数组转换为字符串行
    private function arr2line($arr){
        $line = "";
        foreach($arr as $k=>$v){
            $line .= $v.PHP_EOL;
        }
        return $line;
    }
    
    //列表页使用的字段信息
    private function _getAdminIndexFieldsCode($tableName,$appName,&$useInfo){
        $code = "";
        //$useDb = "";////{USECOMMON_DB}
        $tableName = Common::getDbPrefix().$tableName;
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows($tableName);
        foreach ($fields as $k=>$v){
            //处理设置了查询条件的字段
            if(strlen($v['control_type'])>0){
                $fieldName = $v['field_name'];//字段名称
                $fieldTag  = $v['field_tag'];//字段说明
                $comp_type = $v['compare_type'];//匹配方式
                $ctr_type  = trim($v['control_type']);//控件类型 text text-date  list check...
                $defaultText = isset($v['control_default'])? trim($v['control_default']) : '';//默认值
                $defaultNum  = isset($v['control_default'])? intval($v['control_default']) : 0;//默认值
                $checkV0   = isset($v['ctr_check_v0']) ? intval($v['ctr_check_v0']) : 0;//check未选中的值
                $checkV1   = isset($v['ctr_check_v1']) ? intval($v['ctr_check_v1']) : 1;//check选中的值
                $minVal    = intval($v['ctr_min_val']);//最小有效值
                $clear     = '$FIELDNAME = Common::clearHtmlStr($FIELDNAME);';
                $assign0   = '$this->assign("FIELDNAME",$FIELDNAME);';
                $assign    = <<<ASSIGN
if(\$FIELDNAME > $defaultNum){
            \$this->assign("FIELDNAME",\$FIELDNAME);
        }
ASSIGN;

                //设置默认文本框类内容
                $text = <<<TEXT
\t\t//$fieldTag text FIELDNAME $ctr_type  $comp_type
\t\t\$FIELDNAME = isset(\$params['FIELDNAME']) ?  trim(\$params['FIELDNAME']) : '';
        \$this->assign("FIELDNAME",\$FIELDNAME);
        \$FIELDNAME_row = ['fieldName'=>'FIELDNAME','fieldValue'=>\$FIELDNAME];
        array_push(\$fields,\$FIELDNAME_row);
TEXT;
                
                switch ($ctr_type){
                    case 'text':
                        $temp =  <<<TEXT
\t\t//$fieldTag text FIELDNAME $ctr_type  $comp_type
\t\t\$FIELDNAME = isset(\$params['FIELDNAME']) ?  trim(\$params['FIELDNAME']) : '';
        ASSIGN
        CLEARSTR
        \$FIELDNAME_row = ['fieldName'=>'FIELDNAME','fieldValue'=>\$FIELDNAME];
        array_push(\$fields,\$FIELDNAME_row);
TEXT;
                        //当处理数值数据时,不清理字符
                        if(substr($comp_type,0,6) === 'number'){
                            $temp = str_replace('ASSIGN',$assign,$temp);
                            $temp = str_replace('CLEARSTR','',$temp);
                        }else{
                            $temp = str_replace('ASSIGN',$assign0,$temp);
                            $temp = str_replace('CLEARSTR',$clear,$temp);
                        }
                        break;
                    case 'text-year':
                        $temp = $text;
                        break;
                    case 'text-date':
                        $temp = $text;
                        break;
                    case 'text-datetime':
                        $temp = $text;
                        break;
                    case 'list':
                        $temp = <<<CD
\t\t//$fieldTag list FIELDNAME $ctr_type  $comp_type
\t\t\$FIELDNAME = isset(\$params['FIELDNAME']) ?  \$params['FIELDNAME'] : '$defaultText';
        \$this->assign("FIELDNAME_tree",DBNAME::getListTree(\$FIELDNAME,true));
        \$FIELDNAME_row = ['fieldName'=>'FIELDNAME','fieldValue'=>\$FIELDNAME];
        array_push(\$fields,\$FIELDNAME_row);
CD;
                        //获取数据类名 字段sex_id -> DbSexId
                        $db_name = 'Db'.$this->strConvertClass($fieldName);//DbSexId
                        $use_db  = "use app\\$appName\common\\$db_name;";
                        $temp = str_replace('DBNAME::',$db_name.'::',$temp);
                        
                        if(!in_array($use_db,$useInfo)){
                            array_push($useInfo,$use_db);
                        }
                        break;
                    case 'check':
                        //$this->assign("flag",$flag>0?'checked':'');
                        $temp = <<<CD
\t\t//$fieldTag check FIELDNAME $ctr_type  $comp_type
\t\t\$FIELDNAME = isset(\$params['FIELDNAME']) ?  intval(\$params['FIELDNAME']) : $checkV0;
        \$this->assign("FIELDNAME",\$FIELDNAME === $checkV1 ? 'checked':'');
        \$FIELDNAME_row = ['fieldName'=>'FIELDNAME','fieldValue'=>\$FIELDNAME];
        array_push(\$fields,\$FIELDNAME_row);
CD;
                    break;
                    default:
                        $temp = $text;
                }
                $code .= str_replace('FIELDNAME',$fieldName,$temp).PHP_EOL.PHP_EOL;
            }
        }
        return $code;
    }
    

    //生成列表页顶部查询部分代码,参数为 表名称/是否自定义分页
    private function getIndexQueryCode($appName,$tableName,$pageFlag){
        //获取指定字段,并过滤所需字段
        $code = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        foreach($fields as $k=>$v){
            if(strlen($v['control_type']) > 0){
                $code .= $this->_makeIndexQueryCode($appName,$v);//按字段信息逐一实现
            }
        }
        //是否可设置分页数
        if(intval($pageFlag) > 0){
            $code .=<<<CODE
\t\t&nbsp;每页行数:
\t\t<input type="text" name="page_num" class="form-control" value="{\$page_num|default='10'}" style="width: 50px">
CODE;
        }else{
            $code .=<<<CODE
<input type="hidden" name="page_num" value="{\$page_num|default='10'}">
CODE;
        }
      return $code;
    }
    private function _makeIndexQueryCode($appName,$field){
        $code = "";
        $fieldName = trim($field['field_name']);//字段名称
        $fieldPlaceholder = "请输入".trim($field['field_tag']);
        $fieldNameUse = "{\$".$fieldName."|default=''}";//引用字符 {$abc|default=''}
        $fieldNameUseTree = "{\$".$fieldName."_tree|default=''}";//引用字符 {$abc_tree|default=''}
        $checkStatus = '{$'.$fieldName.'|default=""}';
        switch($field['control_type']){
            case 'text'://普通文本框
                $code = <<<CODE
\t\t&nbsp;{$field['field_tag']}:
\t\t<input type="text" class="form-control" name="$fieldName" value="$fieldNameUse" placeholder="{$fieldPlaceholder}">
CODE;
                break;
            case 'text-year'://年度选择文本框
                $code = <<<CODE
\t\t&nbsp;{$field['field_tag']}:
\t\t<input type="text" class="form-control js-bootstrap-year" name="$fieldName" value="$fieldNameUse" placeholder="{$fieldPlaceholder}">
CODE;
                break;
            case 'text-date':
                $code = <<<CODE
\t\t&nbsp;{$field['field_tag']}:
\t\t<input type="text" class="form-control js-bootstrap-date" name="$fieldName" value="$fieldNameUse" placeholder="{$fieldPlaceholder}">
CODE;
                break;
            case 'text-datetime':
                $code = <<<CODE
\t\t&nbsp;{$field['field_tag']}:
\t\t<input type="text" class="form-control js-bootstrap-datetime" name="$fieldName" value="$fieldNameUse" placeholder="{$fieldPlaceholder}">
CODE;
                break;
            case 'list':
                $code = <<<CODE
 \t\t&nbsp;{$field['field_tag']}:
 \t\t<select title="请选择{$field['field_tag']}" class="form-control" name="$fieldName">$fieldNameUseTree</select>
CODE;
                //列表控件,如果有自定义列表则要生成数据类
                //$this->makeAdminDbFile($appName,$field);
                break;
            case 'check':
                $checkV1 = isset($field['ctr_check_v1']) ? intval($field['ctr_check_v1']) : 1;//选中的值
                $code = <<<CODE
\t\t&nbsp;{$field['field_tag']}:
\t\t<div class="checkbox" style="margin-bottom: 3px">
            <label class="checkbox-inline" title="请确认{$field['field_tag']}">
                <input type="checkbox" id="$fieldName" name="$fieldName" value="$checkV1" $checkStatus>
                {$field['field_tag']}
            </label>
        </div>
CODE;
                break;
            default:
        }
        return $code.PHP_EOL;
    }
    //生成数据类文件,用于自定义列表. 生成位置  app/APPNAME/common 文件名sex_id(字段名)->DbSexId.php
    private function makeAdminDbFile($appName,$field){
        $fieldName = $field['field_name'];
        $jsonDb = trim($field['ctr_select_item']);//自定义的数据
        $tableDb = trim($field['ctr_select_table_info']);//配置的数据表数据
        $fieldTag  = trim($field['field_tag']);
        $defaultVal = $field['control_default'];
        
        $info = new Info();//返回消息
        $appPath = APP_PATH;//D:\phpEnv\www\xxx.com/app/
        //创建common文件夹
        $path = $appPath.$appName.'/common/';
        //把字段名转换为类名称  zzh_abc  -> ZzhAbc
        $className = 'Db'.$this->strConvertClass($fieldName);
        $filename  = $className.".php"; //ZzhAbc ->  DbZzhAbc.php
        $file = $path.$filename;
        $info->message = "<span class='text-warning'>列表数据类文件--></span><br>"
            .str_replace(CMF_ROOT,'',$file);
        $createDir = $this->makeDir($path);
        if($createDir){
            //生成数据类内容
            
            $content = $this->getDbfileText($appName,$className,$jsonDb,$tableDb,$fieldTag,$defaultVal);
            $fw = $this->file_write($file,$content);//创建或更新文件
            if($fw === false){
                $info->code=1;$info->message="<span class='text-danger'>无法创建文件:".$file."</span>";
            }
        }else{
            $info->code=1;$info->message="<span class='text-danger'>无法创建目录:".$path."</span>";
        }
        return $info;
    }
    //生成列表页中部列表
    private function getIndexListThTdCode($tableName,$ThTd){
        //获取指定字段,并过滤所需字段
        $code = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        $tab = 0;
        foreach($fields as $k=>$v){
            if(intval($v['used_list']) > 0){
                if($ThTd === 'th'){
                    $code .= ($tab>0 ? "\t\t\t\t": "").'<th width="100">'
                        .$v['field_tag'].'</th>'.PHP_EOL;//字段标题
                }elseif ($ThTd === 'td'){
                    $code .=  ($tab>0 ? "\t\t\t\t\t\t": "").'<td>{$vo.'.trim($v['field_name'])
                        .'}</td>'.PHP_EOL;//字段值
                }
                $tab +=1;
            }
        }
        return $code;
    }
    //生成列表页底部字段统计
    private function getIndexCountCode($tableName){
        //获取指定字段,并过滤所需字段
        $code = "";
        $pcfm = new PluginCodeFieldsModel();
        $fields = $pcfm->getRows(Common::getDbPrefix().$tableName);
        $tab = 0;
        //合计:{$total|default='0'}
        foreach($fields as $k=>$v){
            if(intval($v['used_count_sum']) > 0){
                $code .= ($tab>0 ? "\t\t\t&nbsp;&nbsp;": "").$v['field_tag']
                    ."合计数:{\$sum_".$v['field_name']."|default='0'}".PHP_EOL;
                $tab += 1;
            }
            if(intval($v['used_count_avg']) > 0){
                $code .= ($tab>0 ? "\t\t\t&nbsp;&nbsp;": "").$v['field_tag']
                    ."平均数:{\$avg_".$v['field_name']."|default='0'}".PHP_EOL;
                $tab += 1;
            }
            if(intval($v['used_count_max']) > 0){
                $code .= ($tab>0 ? "\t\t\t&nbsp;&nbsp;": "").$v['field_tag']
                    ."最大值:{\$max_".$v['field_name']."|default='0'}".PHP_EOL;
                $tab += 1;
            }
            if(intval($v['used_count_min']) > 0){
                $code .= ($tab>0 ? "\t\t\t&nbsp;&nbsp;": "").$v['field_tag']
                    ."最小值:{\$min_".$v['field_name']."|default='0'}".PHP_EOL;
                $tab += 1;
            }
        }
        return $code;
    }

    
    //建立文件夹
    private function makeDir($dir){
        if(!file_exists($dir)){
            return mkdir($dir,0777,true);
        }
        return true;
    }
    //写文件,存在则覆盖
    private function file_write($file,$content){
        //如果文件不存在，则创建一个新文件  FILE_APPEND参数代表追加内容,不提供则覆盖
        return  file_put_contents($file,$content);
        //成功返回字符数,失败返回假.
    }
    //读取文件所有行
    public function file_getLines($file){
        $content = [];
        if(!file_exists($file)){
            return null;
        }
        $handle = fopen($file,"r");
        while(!feof($handle)){
            $line = fgets($handle);
            array_push($content,$line);
        }
        fclose($handle);
        return $content;
    }

    //把数据表名转换为模型名称  abc_def ->  AbcDef
    //带下划线时,如果后面有字母则变是大写,首字母大写
    public function strConvertClass($str,$append=''){
        $str = strtolower($str);//统一为小写字符
        $str = str_replace('__','_',$str);//防止有连续下划线
        $newStr = '';
        $len = strlen($str);
        if($len<=0){
            return "error";
        }
        $flag = false;//上一字符是否为下划线
        for($i=0;$i<$len;$i++){
            if($i == 0){
                //首字符大写
                $newStr = strtoupper($str[$i]);
            }else{
                if($str[$i] === '_'){
                    $flag = true;
                }else{
                    if($flag){
                        $newStr .= strtoupper($str[$i]);
                        $flag = false;
                    }else{
                        $newStr .= $str[$i];
                    }
                }
            }
        }
        return $newStr.$append;
    }
    //检测json格式正确性,正确返回真
    public function checkJson2Arr($json){
        $arr = json_decode($json,true);
        if($arr === null){
            return false;
        }
        return true;
    }
    //检测json格式正确性,正确返回真
    public function checkArr2Json($arr){
        $json = json_encode($arr);
        if($json === false){
            return false;
        }
        return true;
    }
    //json返回数组
    public function json2Array($json){
        $arr = json_decode($json,true);
        if($arr === null){
            return [];
        }
        return $arr;
    }
    //数组转json
    public function array2Json($arr){
        $json = json_encode($arr);
        if($json === false){
            return "";
        }
        return $json;
    }
    //设置缓存
    public function setCache($name,$val){
        cache($name,$val);
    }
    //读取缓存
    public function getCache($name){
        $val = cache($name);
        if(is_null($val) || empty($val)){
            return "";
        }
        return trim($val);
    }
    

}