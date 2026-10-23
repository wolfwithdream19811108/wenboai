## d_tag [thinkcmf插件]
> 文章热门标签展示。  


### 插件配置

* 是否开启
* 标签显示个数

### 安装及说明

1  d_tag 目录添加到public/plugins目录下
2  后台插件中心-》插件列表找到 DTag 安装
3  添加钩子

更改模板文件 public/themes/simpleboot3/portal/article.html 大约130行左右  插件实现了before_right_sidebar_end 这个模板钩子

```
----------------------------------------
</div>
<include file="public@footer"/>

修改为为

	<hook name="before_right_sidebar_end"/>
</div>
<include file="public@footer"/>
----------------------------------------
```
  
4  更改标签模板每页文章显示数

更改模板文件 public/themes/simpleboot3/portal/tag.html

```
//大约23行左右 每页显示10条
----------------------------------------
'list_rows'=>1,  

修改为  

'list_rows'=>10, 
----------------------------------------
```

5  美化显示标签 (添加样式)

```
将下列样式添加到 public/themes/simpleboot3/public/assets/css/style.css 文件底部
----------------------------------------
.d-tag{padding:0.3em 0 0.6em;}
.d-tag .label{
    display:inline-block;
    border-radius: .25em;
    padding: .6em;
    margin:0.5em 0.3em 0.5em 0;
}
.d-tag a {
    color:white;
}
.d-tag a:hover{
    text-decoration: none;
}
----------------------------------------

如果你不想更改上面style.css文件的话，那么在 public/plugins/d_tag/view/widget.html 页面顶部添加如下代码
----------------------------------------
<style type="text/css">
.d-tag{padding:0.3em 0 0.6em;}
.d-tag .label{
    display:inline-block;
    border-radius: .25em;
    padding: .6em;
    margin:0.5em 0.3em 0.5em 0;
}
.d-tag a {
    color:white;
}
.d-tag a:hover{
    text-decoration: none;
}
</style>
----------------------------------------
```
  
6 添加路由(美化标签页访问地址)

* 后台-》设置-》url美化-》添加url规则
* 原始网址： portal/Tag/index
* 显示网址： tag/:id
* 启用-》保存

美化样式建议添加到 public/themes/simpleboot3/public/assets/css/style.css ，样式可以改为自己喜欢的。
标签是按照每个标签文章总数量按多到少依次排序的。
这里只是添加到了文章详情页，其他页面(列表、搜索、标签页)都是一样设置，按照步骤3 在相应模板相应位置添加`<hook name="before_right_sidebar_end"/>`钩子即可



### 问题反馈
在使用中有任何问题，欢迎反馈给我，可以用以下联系方式跟我交流

* 邮件(3471677985#qq.com, 把#换成@) 
* thinkcmf官网d_tag插件详情页留言

Copyright (c) 2018 daliycode.com
