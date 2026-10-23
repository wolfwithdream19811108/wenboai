//配置参数
const DEFAULT_CHANGE_MATERIAL_IMG_NUM = 4
const OPEN_3D_CREATE = true
const isSpeedUp = false;
const isAddChannel = true;
const ADD_CHANNEL_NUM = 4;
const ACCELERATE_STEP = 2;



const input_textarea = document.getElementById('input_textarea')
const detail_clean = document.getElementById('detail_clean')
const now_text_num = document.getElementById('now_text_num')
const size_each = [...document.getElementsByClassName('size-each')]
/*const near_prev = document.getElementById('near_prev')
const near_input = document.getElementById('near_input')
const near_next = document.getElementById('near_next')*/
const count_prev = document.getElementById('count_prev')
const count_input = document.getElementById('count_input')
const count_next = document.getElementById('count_next')
const submit_btn = document.getElementById('submit_btn')
const img_container = document.getElementById('img_container')
const yunkuimglist = document.getElementById('yunkuimglist');
var chatDesignLoading = document.getElementById('chat-design-loading');
var firstCreate = true
var expectedSecondArray = null
var createImageing = false;
var platformId = 1//0为6pen，1为wj
var creatingTimer = 0
var wjIsPro = true
var baseModelId = 0 //0为智能模型,也就是使用数据库里默认存储的模型
const listOverlay = document.getElementById('listoverlay');
const everything = document.getElementById('everything');
const everything2art = document.getElementById('everything2art');
const product = document.getElementById('product');
const product2art = document.getElementById('product2art');
const lora = document.getElementById('lora');
const tableContainer = document.getElementById('table-container');
var kimiapiKey = "REMOVED_SECRET";
let currentProduct = null;
let hasControlNet = false;
// 全局变量，存储图片元素
let imageElements = [];
let workKeyIds = [];
var refImgElement = document.querySelector('.ref_image_url');
var refImgWidth = 0;
var refImgHeight = 0;
var controlnetImg = null
var poemMode = "fast"  //kimi为从kimi获取设计,fast为从预置诗词库获取
var startTime = 0;
var hostname = window.location.hostname;
var domain = "https://www."+hostname.split('.').slice(-2).join('.');
const designNumPerPoem = 5;//每个诗句
let hasLoraModel = false;
var loraId = "";
var loraIdentity = "";
var selectedModelId = 0;
var selectedModelName=""
var baseModel = "sd"
var productDesignModelId = 127;//产品暂时统一都用产品美学的ID

let materialDiv,loadingDiv,create3Ddiv;
var isEverythinMode = false



const default_sign_key = "-----BEGIN PRIVATE KEY-----\n" +
        "MIIBVQIBADANBgkqhkiG9w0BAQEFAASCAT8wggE7AgEAAkEA2lguoGoPbEsJ2RCfyMMUmgJ+P60DIp6KfGYREAQt8ykaWRWB/qPnzNVZXq6+drbDSWtxoIKCCGxWFIOz9FZ3AQIDAQABAkAFaGiXuybAg7OFzXtKpHfHuHr2Ymds6aS0D+5ZYWQetQy69blSUae3ENI/vJz9CuR2eHKGah+g1avfyJIbZQdRAiEA/eJgHTeasLOCPfSRmRm+J/vdXa7cTeR6z+mxX4Ny69ECIQDcKfzc5liR27i5Xbw8AAv+rL1sKFpJelViLRPlq/sUMQIgMnkfzI8yZLC7Ut/dVkek40QSRT6vM5kZxV3bUnbKkRECIQCxlXSOwYXjcLVZX1A7nVYPgEnx+cKJzhEc9Xs9IDGPoQIhALXcYRcaydB9yLSqf4LqIXXo8Br9r6lZ0XeoPyxoRkuA" +
        "-----END PRIVATE KEY-----\n";

const default_appId = 'wjok9a9wy8812a9h3b';


var current_appid = default_appId;
var current_sign_key = default_sign_key;



const api_gateway = "hmys"//hmys or wj
const api_record_works_key_url = domain+ '/api/ai/aiimage/recordWorksKey'
const api_update_works_url = domain+ '/api/ai/aiimage/updateWorksInfo'
const api_get_user_points_url = domain+ '/api/ai/aiimage/getUserPoints'
const api_hm_create_url = domain+'/api/ai/aiimage/create'
const api_get_aiimage_status_url = domain+'/api/ai/aiimage/getImageStatus'
const api_get_aiimage_generatinginfo_url = domain+'/api/ai/aiimage/getGeneratingInfo'
const api_moonshot_url = domain+'/api/ai/aiimage/moonshot'
const api_acclerate_url = domain+'/api/ai/aiimage/acclearate'
const charge_url = domain +'/ai/purchase'
const api_get_aikey = domain+'/api/ai/aiimage/getUserAikey'

const upload_imgage_url_prefix = domain+'/upload/'

document.addEventListener('DOMContentLoaded', function() {
    var toggleSwitch = document.getElementById('toggle-switch');
    var switchText = document.getElementById('switch-text');
    if (toggleSwitch && switchText) {
        toggleSwitch.addEventListener('change', function(e) {
            if (e.target.checked) {
                switchText.textContent = '开';
                isEverythinMode = true;
            } else {
                switchText.textContent = '关';
                isEverythinMode = false;
            }
        });
    } else {
        console.error('One or more elements not found');
    }
    
    
    updateAikey();
    
    
});

// 如果图片尚未加载完成，可以监听load事件
if(refImgElement != null){
    refImgElement.onload = function() {
        var refImgWidth = this.naturalWidth;
        var refImgHeight = this.naturalHeight;
        console.log('图片的宽度是：' + refImgWidth + 'px');
        console.log('图片的高度是：' + refImgHeight + 'px');
    }
}

if(listOverlay != null){
    // 添加点击事件监听器到listoverlay
    listOverlay.addEventListener('click', function(event) {
        // 检查点击事件的目标是否是table-container或其子元素
        if (event.target !== tableContainer && !tableContainer.contains(event.target)) {
            // 如果不是，则移除listoverlay的active类
            listOverlay.classList.remove('active');
        }
    })
}

// 添加点击事件监听器到listoverlay
everything.addEventListener('click', function(event) {
    // 检查点击事件的目标是否是table-container或其子元素
    if (event.target !== everything2art && !everything2art.contains(event.target)) {
        // 如果不是，则移除listoverlay的active类
        everything.classList.remove('active');
    }
})

product.addEventListener('click', function(event) {
    // 检查点击事件的目标是否是table-container或其子元素
    if (event.target !== product2art && !product2art.contains(event.target)) {
        // 如果不是，则移除listoverlay的active类
        product.classList.remove('active');
    }
})


if (yunkuimglist) {
    yunkuimglist.addEventListener('click', function(event) {
        // 检查点击事件的目标是否是 everything2art 或其子元素
        var everything2art = document.getElementById('everything2art'); // 假设这是你要检查的元素的ID
        if (event.target !== everything2art && !everything2art.contains(event.target)) {
            // 如果不是，则移除 yunkuimglist 的 active 类
            yunkuimglist.classList.remove('active');
        }
    });
} else {
    console.error('Element with id "yunkuimglist" not found');
}

createImgGrid(1)



input_textarea.addEventListener('input', function (e) {
    const value = e.target.value
    if (value.length !== 0) {
        detail_clean.style.display = 'inline-block'
        now_text_num.innerHTML = value.length
    }
})
detail_clean.addEventListener('click', function (e) {
    input_textarea.value = ''
    now_text_num.innerHTML = 0
    this.style.display = 'none'
})
prompt_preset.addEventListener('click', async function (e) {
    input_textarea.value = ''
    now_text_num.innerHTML = 0
    
    if(!selectedModelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
         var model_info = await getModelInfo(selectedModelId);
         input_textarea.value = model_info.default_prompt
         console.log(model_info.default_prompt)
    }
    
})
prompt_random.addEventListener('click', async function (e) {
    

    if(!selectedModelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        promptArray = await getModelPrompt(selectedModelId)
        // 使用 Math.random 和 Math.floor 来获取随机索引
        let randomIndex = Math.floor(Math.random() * promptArray.length);
        
        // 使用随机索引来访问并获取随机对象
        let randomPromptObject = promptArray[randomIndex];
        input_textarea.value = randomPromptObject.pre_prompt+randomPromptObject.tag_prompt
    }    

})
prompt_optimiz.addEventListener('click', async function (e) {
    if(!selectedModelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        var prompt = input_textarea.value
        var promptOptimized = await kimiOptimize(prompt)
        console.log(promptOptimized)
        input_textarea.value = prompt+","+promptOptimized
        
    }    

})

prompt_shape.addEventListener('click', async function (e) {
    layer.msg('此功能正在开发中', {icon: 1,time: 2000});
})
prompt_pattern.addEventListener('click', async function (e) {

        // 使用layer.open方法打开iframe层
    layer.open({
      type: 2, // 表示这是一个iframe层
      title: '选择纹样', // 弹出层的标题
      shade: 0.3, // 遮罩透明度
      shadeClose: true, // 点击遮罩关闭层
      area: ['1400px', '800px'], // 弹出层的宽高
      content: '/ai/index/pattern_img_list', // iframe的URL
      success: function(layero, index){
        // layero是弹出层的DOM对象，index是弹出层的索引
        console.log(layero, index);
      }
    });
    if (event) {
        event.stopPropagation();
    }
   

})
prompt_material.addEventListener('click', async function (e) {
    layer.msg('此功能正在开发中', {icon: 1,time: 2000});
})



function openPattern(category) {
    
    baseModelId = 305;//301 mj5.2 302 niji5 303 mjv6 304 niji6 305mj6.1
    selectedModelId = 4001;
    loraIdentity = "";
    
    
    
  layer.open({
      type: 2, // 表示这是一个iframe层
      title: '选择纹样', // 弹出层的标题
      shade: 0.3, // 遮罩透明度
      shadeClose: true, // 点击遮罩关闭层
      area: ['1400px', '800px'], // 弹出层的宽高
      content: '/ai/index/pattern_img_list?category='+category, // iframe的URL
      success: function(layero, index){
        // layero是弹出层的DOM对象，index是弹出层的索引
        console.log(layero, index);
      }
    });
    if (event) {
        event.stopPropagation();
    }    
}


function charge(){
    var index = layer.open({
        type: 2,
        title: '积分充值',
        scrollbar:false,
        maxmin: false,
        closeBtn:1,
        shade: [0.8, '#393D49'],
        shadeClose:true,
        skin: 'myskin',
        anim: 2,
        area: ['700px','600px'],
        
        content: [charge_url, 'no'],
        end: function(){
            updatePoints()
        }
    
    });
    console.log(index)   
}
function isImageUrl(url) {
  // 定义一个正则表达式，匹配常见的图片文件扩展名
  /*const imageExtensions = /\.(jpeg|jpg|png|gif|bmp|svg)$/i;*/
  const imagePattern = /\.(jpe?g|png|gif|bmp|svg)(?:\!|\?|$)/i;
  // 使用正则表达式测试URL
  return imagePattern.test(url);
}
function getRefImage(){
    var refImgUrl = $(".ref_image_url").val();
    var imgSrc = document.getElementById('ref_image_url').src;
    if (refImgUrl) {
        document.getElementById("ref-img-row").style.display = "flex";
        document.getElementById("dashed-box").style.display = "none";
        document.querySelector(".ref-img-container img").src = image_url = imgSrc;
    } else {
        document.getElementById("ref-img-row").style.display = "none";
        document.getElementById("dashed-box").style.display = "flex";
    }
}  

function setRefImage(imgurl){
    document.getElementById("ref-img-row").style.display = "flex";
    document.getElementById("dashed-box").style.display = "none";
    document.querySelector(".ref-img-container img").src = imgurl;
}
function deleteRefImage(){
    document.getElementById("ref-img-row").style.display = "none";
    document.getElementById("dashed-box").style.display = "flex";
    document.querySelector(".ref-img-container img").src = image_url = "";
}
function activeItem(item){
    const items = document.querySelectorAll('.item');
    items.forEach(function (i) {
        i.classList.remove('selected');
    });
    // 再给当前点击的 .item 元素添加 'selected' 类
    item.classList.add('selected');
}
function openTab(evt, tabName) {
    console.log(tabName)
    if(tabName == "shape"){
        layer.msg('稍后开放此功能', {icon: 1,time: 2000});
        return
    }
    // 获取所有选项卡按钮和内容区域
    var tablinks = document.getElementsByClassName("tablinks");
    var tabcontent = document.getElementsByClassName("tabcontent");
    
    // 移除所有选项卡按钮的active类和所有内容区域的显示
    for (var i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    for (var i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
    }
    
    // 给当前点击的选项卡按钮添加active类，并显示对应的内容区域
    evt.currentTarget.className += " active";
    document.getElementById(tabName).style.display = "block";
    baseModelId = 0; //恢复成数据库存储的默认模型
}
async function isLogin(){
    url = domain+'/api/portal/aiimage/isLogin'
    await fetch(url,{
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        })
        .then(response => response.json())
        .then(result => {
            console.log(result)
            if (result.data) {
                return true
            }else {
                return false
            }    
        })
        .catch(error => {
            console.log('error', error);
            return false
        });     
}
async function getEverything(category){
  //const url = domain+'/api/portal/aiimage/getEverything?category='+category;
    const url = `/api/portal/aiimage/getEverything`;
  try {
    const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        });
        const data = await response.json();
        if (data.code === 1 && data.data.list.length) {
            console.log( data.data.list)
            everythingimgArray = data.data.list
            // 获取my-gallery-container元素
            var galleryContainer = document.getElementById('my-gallery-container');
            galleryContainer.innerHTML = '';
            // 遍历everythingimgArray数组col-lg-4 col-md-4 col-sm-6 col-xs-12 padding
            $.each(everythingimgArray, function(index, vo) {
/*                var div = document.createElement('div');
                div.className = 'col-lg-4 col-md-4 col-sm-6 col-xs-12 padding';*/
                var item = document.createElement('div');
                item.className = 'item';
                const items = document.querySelectorAll('.item');
                item.addEventListener('click', function () {
                    // 当点击时，先移除所有 .item 元素的 'selected' 类
                    items.forEach(function (i) {
                        i.classList.remove('selected');
                    });
                    // 再给当前点击的 .item 元素添加 'selected' 类
                    this.classList.add('selected');
                
                });       
                var imgContainer = document.createElement('div');
                imgContainer.className = 'blog-img img';
        
                var figure = document.createElement('figure');
                figure.className = 'effect-ruby';
        
                var img = document.createElement('img');
                img.className = 'img002';
                img.src = vo.url;
                figure.appendChild(img);
                imgContainer.appendChild(figure);
                item.appendChild(imgContainer);
                

                
                
                /*div.appendChild(item);*/
                galleryContainer.appendChild(item);
            });
            	$("#my-gallery-container").mpmansory(
				{
					childrenClass: 'item', // default is a div
					columnClasses: 'padding', //add classes to items
					breakpoints:{
						lg: 4, //一行4个
						md: 4, 
						sm: 6,
						xs: 12
					},
					onload: function (items) {
						//make somthing with items
					} 
				}
			);

        } else {
          return null;
        }
  } catch (error) {
    console.error(`Error fetching everything  for category: ${category}`, error);
    return null;
  }
}

function autoArt(){
     if(!selectedModelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        chatDesignLoading.style.display = 'flex'
        chatDesign(selectedModelName,8)
    }    
}
function poemToArt() {

     if(!selectedModelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        chatDesignLoading.style.display = 'flex'
        poemDesign(selectedModelName,5)
    }    
}

function storyToArt() {
  
     if(!selectedModelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        chatDesignLoading.style.display = 'flex'
        bookDesign(selectedModelName,10)
    }     
}

function everythingToArt() {
  
     if(!selectedModelName){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        if(createImageing){
            layer.confirm('正在生成中，是否停止当前生成任务，开始新的生成', {
                btn: ['确定', '取消'] //可以无限个按钮
            }, function(index, layero){
                layer.close(index); 
                stopCreateImg()
                document.getElementById('everything').classList.add('active');
                            // 获取input元素
                var inputElement = document.getElementById('everythingPrompt');
                inputElement.textContent = '';
                // 动态设置placeholder属性的值
                inputElement.setAttribute('placeholder', '请输入新的提示信息');
            }, function(index){
                layer.close(index); 
                return;
            });
            
        }else{
            document.getElementById('everything').classList.add('active');
                        // 获取input元素
            var inputElement = document.getElementById('everythingPrompt');
            inputElement.value = '';
            // 动态设置placeholder属性的值
            inputElement.setAttribute('placeholder', '请输入新的提示信息');

        }        
    }  
}

function productToArt() {
  
     if(!selectedModelName){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        if(createImageing){
            layer.confirm('正在生成中，是否停止当前生成任务，开始新的生成', {
                btn: ['确定', '取消'] //可以无限个按钮
            }, function(index, layero){
                layer.close(index); 
                stopCreateImg()
                document.getElementById('product').classList.add('active');
                            // 获取input元素
                var inputElement = document.getElementById('productPrompt');
                inputElement.textContent = '';
                // 动态设置placeholder属性的值
                inputElement.setAttribute('placeholder', '请输入新的提示信息');
            }, function(index){
                layer.close(index); 
                return;
            });
            
        }else{
            document.getElementById('product').classList.add('active');
                        // 获取input元素
            var inputElement = document.getElementById('productPrompt');
            inputElement.value = '';
            // 动态设置placeholder属性的值
            inputElement.setAttribute('placeholder', '请输入新的提示信息');

        }        
    }  
}

async function selectModel(modelId,modelName){
    selectedModelId = modelId;
    selectedModelName = modelName;
    loraIdentity = "";
    var model_info = await getModelInfo(selectedModelId);
    if(model_info.is_lora){
        loadlora(modelId,modelName,model_info.lora_id,model_info.lora_weight,model_info.trigger_word,model_info.base_model,false)
    }
    
    
}

function loadMaterialLoraAndCreateImg(modelId,modelName,loraId,loraWeight,triggerWord,base_model,islora) {
    if(islora){
        loadlora(modelId,modelName,loraId,loraWeight,triggerWord,base_model,true)
    }else{
        selectedModelId = modelId
        selectedModelName = modelName
        baseModel = base_model
        lora.classList.remove('active');
        hasControlNet = true        
    }
    //更换材质默认4张
    count_input.value = DEFAULT_CHANGE_MATERIAL_IMG_NUM
    stopCreateImg()
    startCreateImg()       

}
function loadlora(modelId,modelName,loraId,loraWeight,triggerWord,base_model,ControlNet) {
    selectedModelId = modelId
    selectedModelName = modelName
    baseModel = base_model
    loraIdentity = `<lora:lora-${loraId}:${loraWeight}>${triggerWord ? `:${triggerWord}` : ''}`;
    console.log("loraIdentity:"+loraIdentity)
    lora.classList.remove('active');
    hasControlNet = ControlNet
    
}

function changeMaterial(imgElement) {
    controlnetImg = imgElement.src;
    refImgElement.src = imgElement.src;
    console.log("更换材质图片为："+controlnetImg)
    lora.classList.add('active');
    lora.addEventListener('click', function(event) {
        // 检查点击事件的目标是否是table-container或其子元素
        if (event.target !== everything2art && !everything2art.contains(event.target)) {
            // 如果不是，则移除listoverlay的active类
            lora.classList.remove('active');
        }
    })
    

}
function create3D(imgElement,workKey) {
    console.log('Creating 3D for workKey:', workKey);
    console.log('Image element:', imgElement);
    var workId = workKeyIds?.[workKey] ?? null; 
    console.log('Creating workid:', workId);
    var pic3Durl = imgElement.src;
    console.log("生成3D图片为：" + pic3Durl);

    // 获取当前页面的域名
    const domain = window.location.origin;

    // 构建请求URL
    const requestUrl = `${domain}/threed?modelpic=${encodeURIComponent(pic3Durl)}&userWorkId=${encodeURIComponent(workId)}`;

    // 打开新的标签页
    window.open(requestUrl, '_blank');
}

function fillPrompt(promptContainer,prompt){
    var inputElement = document.getElementById(promptContainer);
    inputElement.value = prompt
}
function setProductInfo(promptContainer,product){
    console.log("选择的模型ID为：",selectedModelId);
    currentProduct = product;
    console.log('当前选中的产品信息:', currentProduct);
    const productinfos = currentProduct['infos'];
    productDesignModelId = currentProduct['product_model_id']
    var inputElement = document.getElementById(promptContainer);
    // 检查 productinfos 是否为有效数组
    if (!Array.isArray(productinfos) || productinfos.length === 0) {
        console.log("无有效产品信息");
        inputElement.value = ""; // 赋值 prompt
        return; // 直接返回，不赋值
    }

    // 遍历数组，查找匹配的 model_id
    for (const info of productinfos) {
        // 检查 info 是否存在且包含 model_id 和 prompt
        if (info && info.model_id == selectedModelId && info.prompt) {
            inputElement.value = info.prompt; // 赋值 prompt
            console.log("已匹配并填充 prompt:", info.prompt);
            return; // 找到后立即返回
        }
    }

    // 没有匹配项或 prompt 为空
    console.log("未找到匹配的 model_id 或 prompt 为空");

}
function addUploadImgInEverythin(){
    var imgurl = "/upload/"+$(".ref_image_url").val();
    var galleryContainer = document.getElementById('my-gallery-container');
    var item = document.createElement('div');
    item.className = 'item';
    
    // 事件处理函数，用于添加和移除 'selected' 类
    function toggleSelectedClass(targetItem) {
        // 先移除所有 .item 元素的 'selected' 类
        const items = document.querySelectorAll('.item');
        items.forEach(function (i) {
            i.classList.remove('selected');
        });
        // 再给当前点击的 .item 元素添加 'selected' 类
        targetItem.classList.add('selected');
    }
    
    // 给item添加点击事件监听
    item.addEventListener('click', function () {
        toggleSelectedClass(this);
    });
    
    // 设置imgContainer的最大宽度为400px
    var imgContainer = document.createElement('div');
    imgContainer.className = 'blog-img img';
    imgContainer.style.maxWidth = '400px'; // 直接设定最大宽度为400px
    imgContainer.style.display = 'flex'; // 使用Flexbox布局
    imgContainer.style.justifyContent = 'center'; // 水平居中
    imgContainer.style.alignItems = 'center'; // 垂直居中
    
    var figure = document.createElement('figure');
    figure.className = 'effect-ruby';
    
    var img = document.createElement('img');
    img.className = 'img002';
    img.src = imgurl;
    figure.appendChild(img);
    imgContainer.appendChild(figure);
    item.appendChild(imgContainer); 
    galleryContainer.appendChild(item);
    
    // 默认选中新添加的item
    toggleSelectedClass(item);
    
}

function everythingDesign() {
    const selectedItem = document.querySelector('.item.selected');
    if (selectedItem) {
        const imgElement = selectedItem.querySelector('.img002');
        prompt = document.getElementById('everythingPrompt').value
        if(prompt.length<=3){
            layer.msg('请输入四个字以上的提示语', {icon: 1,time: 2000});
        }else{
            setRefImage(imgElement.src)
            controlnetImg = imgElement.src;
            input_textarea.value = prompt
            //万物成艺默认6张
            count_input.value = 6
            /* input_textarea.value = ''*/
            startCreateImg()
            document.getElementById('everything').classList.remove('active');       
        }
    }else{
        layer.msg('未选中图像', {icon: 1,time: 2000});
    }
 
} 
function productDesign() {
    if (!currentProduct) {
        layer.msg('请先选择一个产品', {icon: 1,time: 2000});
        return;
    }    
 
    const selectedItem = document.querySelector('.item.selected');
    if (selectedItem) {
        const imgElement = selectedItem.querySelector('.img002');
        prompt = document.getElementById('productPrompt').value
        if(prompt.length<=3){
            layer.msg('请输入四个字以上的提示语', {icon: 1,time: 2000});
        }else{
            setRefImage(imgElement.src)
            controlnetImg = imgElement.src;
            input_textarea.value = prompt
            //默认6张
            count_input.value = 9
            startCreateImg(productDesignModelId)
            document.getElementById('product').classList.remove('active');       
        }
    }else{
        layer.msg('未选中图像', {icon: 1,time: 2000});
    }
 
} 
function styleShift(){
 
    layer.msg('此功能正在研发中....', {
    	  icon: 1,
    	  time: 2000 //2秒关闭
    	});
}
/*
let size_each_index = 0

function changeSizeActive(index) {
    size_each[index].classList.add
}

function clearSizeActive() {
    size_each.forEach(item => {
        item.classList.remove('size-each-active')
    })
}
for (let i = 0; i < size_each.length; i++) {
    size_each[i].addEventListener('click', function (e) {
        clearSizeActive()
        this.classList.add('size-each-active')
        size_each_index = i
    })
}
*/
function cartsBlur(e, max, dom) {
    const value = e.target.value
    if (isNaN(+value)) {
        alert('请输入数字')
        dom.innerHTML = 1
        dom.value = 1
    } else {
        if (+value > max || +value < 1) {
            alert('范围不对')
            dom.innerHTML = 1
            dom.value = 1
        }
    }
}

/*near_prev.addEventListener('click', function (e) {
    const value = near_input.value
    if (value === '1') return
    near_input.value = +value - 1
})
near_next.addEventListener('click', function (e) {
    const value = near_input.value
    if (value === '10') return
    near_input.value = +value + 1
})

near_input.addEventListener('blur', function (e) {
    cartsBlur(e, 10, this)
})*/
count_input.addEventListener('blur', function (e) {
    cartsBlur(e, 9, this)
})

count_prev.addEventListener('click', function () {
    const value = count_input.value
    if (value === '1') return
    count_input.value = +value - 1
})
count_next.addEventListener('click', function () {
    const value = count_input.value
    if (value === '9') return
    count_input.value = +value + 1
})
submit_btn.addEventListener('click', function (e) {

    url = domain+'/api/portal/aiimage/isLogin'
    fetch(url,{
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        })
        .then(response => response.json())
        .then(result => {
            console.log(result)
            if (result.data) {

                if(createImageing){
                    layer.confirm('正在生成中，是否停止当前生成任务，开始新的生成', {
                        btn: ['确定', '取消'] //可以无限个按钮
                    }, function(index, layero){
                        layer.close(index); 
                        stopCreateImg()
                        if(isEverythinMode){
                            startCreateImgWithControlNetMode()
                        }else{
                            startCreateImg()
                        }
                        
                    }, function(index){
                        layer.close(index); 
                    });
                    
                }else{
                    if(isEverythinMode){
                        startCreateImgWithControlNetMode()
                    }else{
                        startCreateImg()
                    }
                }
            }else {
               layer.msg('请登录后再生成', {
                    	  icon: 1,
                    	  time: 2000 //2秒关闭
                    	});  
            }    
        })
        .catch(error => {
            console.log('error', error);
           layer.msg('请登录后再生成', {
                	  icon: 1,
                	  time: 2000 //2秒关闭
                	});  
        });    
    
    
    
})

function getBaseModel() {
    var selectElement = document.getElementById("baseModels");
    var selectedValue = selectElement.value; // 获取选中的值
    console.log("基本模型ID: " + selectedValue);
    baseModelId = selectedValue
    // 这里可以根据需要执行其他操作
}
//overrideModelId是给产品模型使用的，产品模型最后实际调用的和选择的不一致，所以如此处理。
function startCreateImg(overrideModelId = null){
    var prompt = input_textarea.value
    const creatImageNum = count_input.value
    //如果设置了产品model id，则使用产品model id，否则以一开始选择的模型ID为准
    const modelIdToUse = overrideModelId !== null ? overrideModelId : selectedModelId;
    if(prompt.length <=4 ){
        //有controlnet的话，根据图片直接生成，不用描述语
       layer.msg('请输入4个汉字以上的图像描述语', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;
    }
    controlnetImg = null
    if(refImgElement&&refImgElement.src.length>5){
        controlnetImg = refImgElement.src
    }
    
    if(loraIdentity!=""){
        prompt = loraIdentity+","+prompt
    }
    console.log("提示词为："+prompt)
    console.log("selectedModelId for generation："+modelIdToUse) // Log the ID being used
    

    if(modelIdToUse === 0 || modelIdToUse === null){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
         wjSetProVersion()
         createImgGrid(creatImageNum);
        if(isMjMode()){
            //如果是mj模式，则参考图是风格参考图，不是controlnet图
            if(isImageUrl(refImgElement.src)){
                var mjParam = {
                    "chaos": 0,
                    "stylize": 100,
                    "quality": "1",
                    "upbeta": false,
                    "tile": false,
                    "sref_urls": [refImgElement.src],
                    "sw": 100
                };
            }else{
                var mjParam = {
                    "chaos": 0,
                    "stylize": 100,
                    "quality": "1",
                    "upbeta": false,
                    "tile": false
                };                
            }
            
        }
         createImages(prompt, modelIdToUse, creatImageNum,mjParam)
    }    
}

function startCreateImgWithControlNetMode(){
        console.log("execute function startCreateImgWithControlNetMode")
    
        
        hasControlNet = true
        controlnetImg = refImgElement.src
        
        var prompt = input_textarea.value
        const creatImageNum = count_input.value
        if(prompt.length <=4 ){
            //有controlnet的话，根据图片直接生成，不用描述语
           layer.msg('请输入4个汉字以上的图像描述语', {
                	  icon: 1,
                	  time: 2000 //2秒关闭
                	});
            return;
        }
        

        
        if(loraIdentity!=""){
            prompt = loraIdentity+","+prompt
        }
        console.log("提示词为："+prompt)
        console.log("selectedModelId："+selectedModelId)
        

        if(!selectedModelId){
             layer.msg('请选择一个模型', {
                	  icon: 1,
                	  time: 2000 //2秒关闭
                	});
            return;       
        }else{
             wjSetProVersion()
             createImgGrid(creatImageNum);
            if(isMjMode()){
                //如果是mj模式，则参考图是风格参考图，不是controlnet图
                var mjParam = {
                    "chaos": 0,
                    "stylize": 100,
                    "quality": "1",
                    "upbeta": false,
                    "tile": false,
                    "sref_urls": [refImgElement.src],
                    "sw": 100
                };            
                
            }
             createImages(prompt, selectedModelId, creatImageNum,mjParam)
        }    
}





$(document).ready(function() {
      $('.aiimge-slect-box').click(function() {
        // 移除之前被选中的元素的选中状态
        $('.aiimge-slect-box').removeClass('selected');
        // 为当前点击的元素添加选中状态
        $(this).addClass('selected');
      });
      
      getRefImage()
  
  
  
  
    $("#my-gallery-container").mpmansory(
    	{
    		childrenClass: 'item', // default is a div
    		columnClasses: 'padding', //add classes to items
    		breakpoints:{
    			lg: 4, //一行3个
    			md: 4, 
    			sm: 6,
    			xs: 12
    		},
    		onload: function (items) {
    			//make somthing with items
    		} 
    	}
    );  
    $("#product-gallery-container").mpmansory(
    	{
    		childrenClass: 'item', // default is a div
    		columnClasses: 'padding', //add classes to items
    		breakpoints:{
    			lg: 4, //一行3个
    			md: 4, 
    			sm: 6,
    			xs: 12
    		},
    		onload: function (items) {
    			//make somthing with items
    		} 
    	}
    );  
    
    $("#yunku-gallery-container").mpmansory(
    	{
    		childrenClass: 'item', // default is a div
    		columnClasses: 'padding', //add classes to items
    		breakpoints:{
    			lg: 4, //一行3个
    			md: 4, 
    			sm: 6,
    			xs: 12
    		},
    		onload: function (items) {
    			//make somthing with items
    		} 
    	}
    ); 
    
    $("#lora-gallery-container").mpmansory(
    	{
    		childrenClass: 'item', // default is a div
    		columnClasses: 'padding', //add classes to items
    		breakpoints:{
    			lg: 3, //一行4个
    			md: 4, 
    			sm: 6,
    			xs: 12
    		},
    		onload: function (items) {
    			//make somthing with items
    		} 
    	}
    );  
    
	$("#main-menu li.dropdown").hover(function(){
		$(this).addClass("open");
	},function(){
		$(this).removeClass("open");
	});
	
	$("#main-menu a").each(function() {
		if ($(this)[0].href == String(window.location)) {
			$(this).parentsUntil("#main-menu>ul>li").addClass("active");
		}
	}); 
    url = domain+'/api/portal/aiimage/isLogin'
    fetch(url,{
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        })
        .then(response => response.json())
        .then(result => {
            console.log(result)
            if (result.data) {
                user = result.data.user
                $("#main-menu-user span.user-nickname").text(user.user_nickname ? user.user_nickname : user.user_login);
                $("#main-menu-user li.login").show();
                $("#main-menu-user li.offline").hide();
            }else {
                $("#main-menu-user li.login").hide();
                $("#main-menu-user li.offline").show();
            }    
        })
        .catch(error => {
            console.log('error', error);
        });    
 
  
});


function stopCreateImg(){
    console.log(creatingTimer)
    clearInterval(creatingTimer); // 所有id处理完毕后停止定时器
    img_list = Array();
    changeCreateImageStatus(false)    
}

/*function printImageUrls() {
  const imgContainer = document.getElementById('img_container');
  const images = imgContainer.getElementsByTagName('img');

  for (let i = 0; i < images.length; i++) {
    const imageUrl = images[i].getAttribute('src');
    console.log(imageUrl);
  }
}*/

function createImgGrid(imageNum) {
    img_container.innerHTML = ''; // 清空img_container下的所有子节点
    imageElements = []; // 清空之前的引用
    if (imageNum == 1) {
        imageNumPerLine = 1;
        lines = 1;
    } else if (imageNum ==2) {
        imageNumPerLine = 2;
        lines = 1;
    } else if (imageNum >= 3 && imageNum <= 4) {
        imageNumPerLine = 2;
        lines = 2;
    } else if (imageNum >= 5 && imageNum <= 6) {
        imageNumPerLine = 3;
         lines = 2;
    } else if (imageNum >= 6 && imageNum <= 9) {
        imageNumPerLine = 3;
         lines = 3;
    }else {
       console.log("imageNum is error!")
    }
    img_container.style = ` grid-template-columns: repeat(${imageNumPerLine}, 1fr);grid-template-rows: repeat(${lines}, 1fr);`
    
    for (let i = 0; i < imageNum; i++) {
        const each_img_div = document.createElement('div')
        each_img_div.classList.add('each-img')
        each_img_div.dataset.index = i; // 添加索引属性
        
        const ahref = document.createElement('a');
        each_img_div.appendChild(ahref);
        
        const img = document.createElement('img');
        img.style.display = 'none'; 
        ahref.appendChild(img);
        ahref.setAttribute('data-lightbox', "roadtrip");
        ahref.setAttribute('data-title', "中国工艺美术AIGC平台-CARTAI");
        
        var materialDiv = document.createElement('div');
        materialDiv.classList.add('material');
        materialDiv.textContent = '更换材质';
        materialDiv.style.display = 'none'; // 自定义样式，设置高度为50px  
        materialDiv.addEventListener('click', function() {
            changeMaterial(img)
        });    
        each_img_div.appendChild(materialDiv);
       
       
        if(window.open3d){
            var create3Ddiv = document.createElement('div');
            create3Ddiv.classList.add('create3d');
            create3Ddiv.textContent = '3D生成';
            create3Ddiv.style.display = 'none'; // 自定义样式，设置高度为50px  
            create3Ddiv.addEventListener('click', function() {
                // 从父元素获取workKey
                const workKey = this.parentElement.dataset.workkey;
                // 调用create3D时传入workKey
                create3D(img, workKey);
            });  
            each_img_div.appendChild(create3Ddiv);
        }
        
     
        
        const loadingdiv = document.createElement('div');
        loadingdiv.classList.add('loading');
        
        const loadingImg = document.createElement('img');
        loadingImg.src = '/themes/Acme/public/assets/img/loading.gif';
        loadingImg.alt = 'Loading...';
        
        loadingdiv.appendChild(loadingImg);
        loadingImg.style.display = 'block'; // 自定义样式，设置高度为50px  
        if(firstCreate){
            loadingdiv.innerHTML = '赶快生成你的创意设计';
            loadingdiv.style.fontSize = '32px'; // 设置字体大小为16px
        }else{
            loadingdiv.innerHTML += '生成中...';
            loadingImg.style.width = '50px'; // 自定义样式，设置宽度为50px
            loadingImg.style.height = '50px'; // 自定义样式，设置高度为50px  
        }
        each_img_div.appendChild(loadingdiv);
        img_container.appendChild(each_img_div);
        imageElements.push({
            element: each_img_div,
            imgElement: img,
            workKey: null // 初始为null，后续赋值
        });        
        firstCreate = false
    }
}



function changeCreateImageStatus(isCreating){
    const eachImgElements = img_container.getElementsByClassName('each-img'); 
    createImageing = isCreating;
    
    for (var i = 0; i < eachImgElements.length; i++) {
        let div = eachImgElements[i]; // 获取当前使用的div元素
        let loadingDiv  = div.querySelector('.loading'); // 获取div下的loading标签
        let materialDiv = div.querySelector('.material')
        if(createImageing){
            if (loadingDiv) loadingDiv.style.display  = 'block';  
            if (materialDiv) materialDiv.style.display = 'none';
            if (create3Ddiv) create3Ddiv.style.display  = 'none'; 
        }else{
            if (loadingDiv) loadingDiv.style.display  = 'none';   
            if (materialDiv) materialDiv.style.display  = 'block';  
            if (create3Ddiv) create3Ddiv.style.display  = 'block'; 
        }
    }   
    
    
}
function wjSetProVersion(){
    if(baseModelId >= 301 && baseModelId<=305){
        wjIsPro = false
    }else{
        wjIsPro = true
    }
}

function isMjMode(){
    if(baseModelId >= 301 && baseModelId<=305){
        return true
    }else{
        return false
    }
    
}


async function createImages(prompt,selectedModelId, multiply,mjParam) {
    
    if(selectedModelId !=productDesignModelId){
        var model_info = await getModelInfo(selectedModelId);
        
        var imagePrompt = prompt;
        if(model_info!=null){
            baseModel = model_info.base_model;
            if (model_info.pre_prompt) {
              imagePrompt = model_info.pre_prompt + imagePrompt;
            } 
            if (model_info.tail_prompt) {
              imagePrompt = imagePrompt +","+ model_info.tail_prompt;
            }
        }
        imagePrompt = await kimiTranslate(imagePrompt);
        console.log("翻译结果"+imagePrompt)
        // imagePrompt = imagePrompt + ',没有复杂背景，真实自然，细节精准，高清画质，大师作品';
        var model_code = 0
        if(baseModelId!=0) {
            //如果不用智能模型，且不是mj模型，则所有模型用选定的基模型
            model_code = baseModelId
        }  
        else{
            model_code = model_info.model_id_wj
        }
    }else{
        var imagePrompt = prompt;
        model_code = productDesignModelId
    }
    
 
    
    callCreateImages(imagePrompt, model_code, multiply,model_info,mjParam)//141 19剪纸
            .then(result => {
                if (result['idArray'] != null) {
                    
                    // 将workKey分配给对应的图片元素
                    result['idArray'].forEach((workKey, index) => {
                        if (imageElements[index]) {
                            imageElements[index].workKey = workKey;
                            imageElements[index].element.dataset.workkey = workKey;
                        }
                    });                    
                    
        
                    
                    
                    recordWorksKey(result['idArray'])
                    if(isSpeedUp){
                       autoAccelerateWhenNeeded(model_code,result['idArray'])
                    }
                    getImgs(result['idArray']);
                    
                    
                    updatePoints().then(content => {
                        console.log('获取到积分内容：', content);
                        }).catch(error => {
                        console.error('更新积分过程中发生错误：', error);
                        });
                } else {
                    changeCreateImageStatus(false)
                    alert(result['error']);
                    
                }
            })
            .catch(error => console.log('error', error)); 


}
function findWorkIdbyKey(workKey){
    
}
function recordWorksKey(workskeyArray) {
    // 返回一个新的Promise
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', api_record_works_key_url, true);
            
            // 设置请求头部信息
            xhr.setRequestHeader("Content-Type", "application/json");
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        //console.log(response);
                        console.log("recordWorksKey")
                        console.log(response.data);
                        workKeyIds = response.data;
                       // resolve(content); // 解决Promise并返回content
                    } catch (error) {
                        console.error('解析响应内容出错', error);
                        //reject(error); // 拒绝Promise
                    }
                } else {
                    console.error('记录用户作品key失败，状态码：', xhr.status);
                    reject(new Error('记录用户作品key失败')); // 拒绝Promise
                }
            };
            
            xhr.onerror = function() {
                console.error('记录用户请求出错');
                reject(new Error('记录用户请求出错')); // 拒绝Promise
            };
            
            // 将workskeyArray转换为JSON字符串并发送
            xhr.send(JSON.stringify(workskeyArray));
        }, 1000); // 休眠1秒
    });
}

function updateWorksInfo(workKey,data) {
   const pictureUrl = data?.picture_url;
    if (pictureUrl === null || pictureUrl === "") {
        //还未生成，不更新记录
        return
    } 
    var body = {
        key: workKey,
        worksInfo: data
    };    

    
    // 返回一个新的Promise
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            var xhr = new XMLHttpRequest();
            
            xhr.open('POST', api_update_works_url, true);
            
            // 设置请求头部信息
            xhr.setRequestHeader("Content-Type", "application/json");
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        var content = response.data;
                        resolve(content); // 解决Promise并返回content
                    } catch (error) {
                        console.error('解析响应内容出错', error);
                        reject(error); // 拒绝Promise
                    }
                } else {
                    console.error('记录用户作品key失败，状态码：', xhr.status);
                    reject(new Error('记录用户作品key失败')); // 拒绝Promise
                }
            };
            
            xhr.onerror = function() {
                console.error('记录用户请求出错');
                reject(new Error('记录用户请求出错')); // 拒绝Promise
            };
            
            // 将workskeyArray转换为JSON字符串并发送
            xhr.send(JSON.stringify(body));
        }, 1000); // 休眠1秒
    });
}

function updatePoints() {
    // 返回一个新的Promise
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            var xhr = new XMLHttpRequest();
            
            xhr.open('GET', api_get_user_points_url, true);
            
            // 设置请求头部信息
            xhr.setRequestHeader("Content-Type", "application/json");
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        var content = response.data;
                        console.log(content);
                       // 更新DOM元素的内容
                        document.getElementById("user-points").textContent = "积分：" + content;
                       // resolve(content); // 解决Promise并返回content
                    } catch (error) {
                        console.error('解析响应内容出错', error);
                       // reject(error); // 拒绝Promise
                    }
                } else {
                    console.error('更新积分失败，状态码：', xhr.status);
                    reject(new Error('更新积分失败')); // 拒绝Promise
                }
            };
            
            xhr.onerror = function() {
                console.error('请求出错');
                reject(new Error('请求出错')); // 拒绝Promise
            };
            
            xhr.send();
        }, 1000); // 休眠1秒
    });
}


async function getModelInfo(model_id){
  const url = domain+'/api/portal/aiimage/getModelInfo?model_id='+model_id;
  
  try {
    const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        });
        const data = await response.json();
        if (data.code === 1 && data.data.list.length) {
          return data.data.list[0];
        } else {
          return null;
        }
  } catch (error) {
    console.error(`Error fetching model info  for id: ${model_id}`, error);
    return null;
  }
}
async function getModelPrompt(model_id){
  const url = domain+`/api/portal/aiimage/getModelPrompt?model_id=`+model_id;
  
  try {
    const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        });
        const data = await response.json();
        if (data.code === 1 && data.data.list.length) {
            return data.data.list;
        } else {
          return null;
        }
  } catch (error) {
    console.error(`Error fetching model prompt  for id: ${model_id}`, error);
    return null;
  }
}
function findElementByWorkKey(workKey) {
    return imageElements.find(item => item.workKey === workKey);
}
function getImgs(idArray) {
    // 获取每个each-img元素
    const eachImgElements = img_container.getElementsByClassName('each-img');    
    
    var img_list = Array();
    var id_state_list = {};
    for (let i = 0; i < idArray.length; i++) {
        id_state_list[idArray[i]] = 0; // 0表示还未获得生成image的url
    }   
    
    let currentIndex = 0; // 当前使用的div元素的索引
    startTime = new Date().getTime();
    
    creatingTimer = setInterval(async () => {
        const currentTime = new Date().toLocaleTimeString();
        const elapsedTime = (new Date().getTime() - startTime)/1000;
        console.log("已执行"+elapsedTime+"秒");
        
        if (idArray.length > 0) {
            // 获取所有任务的生成信息
            const imageGeneratingInfos = await hmGetImageGenerating_info(idArray);
            
            if (imageGeneratingInfos) {
                for (let i = 0; i < idArray.length; i++) {
                    const key = idArray[i];
                    const info = imageGeneratingInfos.find(item => item.key === key);
                    
                    if (!info) continue;
                    
                    let div = eachImgElements[i]; // 获取当前使用的div元素
                    let loadingDiv = div.querySelector('.loading'); // 获取div下的loading标签

                    // 根据状态更新显示
                    switch(info.status) {
                        case 0: // 已提交
                            loadingDiv.textContent = "已提交，等待处理";
                            break;
                        case 1: // 排队中
                            loadingDiv.textContent = `排队中`;
                            break;
                        case 2: // 生成中
                            percent = (info.complete_percent * 100).toFixed(2);
                            loadingDiv.textContent = `生成中 ${percent}%`;
                            break;
                        case 3: // 生成失败
                            loadingDiv.textContent = "生成失败";
                            if (info.fail_message) {
                                console.error(`生成失败原因: ${info.fail_message.fail_message}`);
                            }
                            break;
                        case 4: // 生成成功
                            if (info.picture_url && !id_state_list[key]) {
                                console.log(`图片生成成功: ${info.picture_url}`);
                                hmGetImageStatus(idArray[i]);
                                id_state_list[key] = 1; // 标记为已处理
                                
                                let div = eachImgElements[currentIndex]; // 获取当前使用的div元素
                                let ahref = div.querySelector('a');
                                ahref.href = info.picture_url;
                                
                                let img = div.querySelector('img');
                                img.src = info.picture_url;
                                img.setAttribute('data-original', info.picture_url);
                                img.style.display = 'block';
                                
                                loadingDiv  = div.querySelector('.loading'); // 获取div下的loading标签
                                loadingDiv.style.display = 'none';
                                
                                materialDiv = div.querySelector('.material');
                                materialDiv.style.display = 'block';
                                
                                create3Ddiv = div.querySelector('.create3d');
                                if (create3Ddiv) create3Ddiv.style.display = 'block';
                                
                                img_list.push(info.picture_url);
                                currentIndex = (currentIndex + 1) % eachImgElements.length; // 更新当前使用的div元素的索引
                            }
                            
      
                            // 检查是否所有图片URL都已获取
                            if (img_list.length === idArray.length) {
                                clearInterval(creatingTimer); // 所有imageUrl成功获取后停止定时器
                                console.log('所有生成任务处理完毕.');
                                clearInterval(creatingTimer); // 所有id处理完毕后停止定时器
                                img_list = Array();
                                changeCreateImageStatus(false)
                                
                                lightbox.option({
                                  'resizeDuration': 200,
                                  'wrapAround': true
                                })
                                
                            }                            
                            
                            
                            
                            
                            break;
                        case 5: // 夜间排队
                            loadingDiv.textContent = "夜间排队中，请耐心等待";
                            break;
                        case -1: // 已撤销
                            loadingDiv.textContent = "任务已撤销";
                            break;
                        default:
                            loadingDiv.textContent = "未知状态";
                    }
                    
                    // 只在状态不为4时添加loading.gif
                    if (info.status !== 4) {
                        loadingDiv.classList.add('loading');
                        const loadingImg = document.createElement('img');
                        loadingImg.src = '/themes/Acme/public/assets/img/loading.gif';
                        loadingImg.alt = 'Loading...';
                        loadingDiv.appendChild(loadingImg);
                        loadingImg.style.display = 'block'; // 自定义样式，设置高度为50px  
                    }                    
                    
                    
                    
                    
                    
                    
                    // 检查是否所有图片URL都已获取
                    if (img_list.length === idArray.length) {
                        clearInterval(creatingTimer);
                        console.log('所有生成任务处理完毕.');
                        img_list = Array();
                        changeCreateImageStatus(false);
                        
                        lightbox.option({
                            'resizeDuration': 200,
                            'wrapAround': true
                        });
                    }
                }
            }
        } else {
            console.log('所有生成任务处理完毕.');
            clearInterval(creatingTimer);
            img_list = Array();
            createImageing = true;
        }
        
        // 检查是否超过8分钟
        if (elapsedTime >= 480) {
            clearInterval(creatingTimer);
            console.log("Timer stopped due to reaching the 480 seconds limit.");
            layer.msg('生成超时，请重新生成', {
                icon: 1,
                time: 2000
            });
            createImageing = false;
        }
    }, 5000); // 每隔5秒执行一次
    
    console.log("getimg timer id:"+creatingTimer);
}

function getCreateUrl(){
    let create_url;
    
    if (api_gateway == "hmys") {
        create_url = api_hm_create_url;
    } else if (api_gateway == "wj") {
        if (!wjIsPro) {
            create_url = "https://gate.wujieai.net/wj-open/v1/ai/create";
        } else {
            create_url = "https://gate.wujieai.net/wj-open/v1/ai/pro/create";
        }
    } else {
        // Default case if neither "hmys" nor "wj"
        create_url = api_hm_create_url; // or set a default URL if needed
    }    
    return create_url
}


async function callCreateImages(prompt, model_code, multiply,model_info,mjParam) {
    
    

    var rawdata = null;
    var auth = getWjAuth();
    var myHeaders = wjGetHeader();
    if(!wjIsPro){
   
        raw = JSON.stringify({
           "model": model_code,
           "prompt": prompt.replace(/{/g, '').replace(/}/g, ''),
           "width": 1024,
           "height": 1024,
           "mj_param":mjParam,
           "num": multiply,
           "auth":auth,
           "isPro":false,
                
        });        
        if(model_info.model_fusion) {
            var option_param= {
                "model_fusion": null
            }
            model_info.model_fusion = model_info.model_fusion.replace(/\\/g, "");
            model_fusion = JSON.parse(model_info.model_fusion)
            option_param.model_fusion = model_fusion
            raw.model_fusion = model_fusion;
        }
    

    }else{
        //专业版接口
        var rawdata = {
           "model_code": parseInt(model_code),
           "prompt": prompt,
           "auth":auth,
           "isPro":true,
           "width": 1024,
           "height": 1024,
           //"prefine_multiple": 1.5,
           "batch_count": multiply,
           "option_param": {
                "model_fusion": null
            },
           "control_net_params": null,
            "advanced_param":{
                "cfg": 8,
                "ensd": 0,
                "restore_faces": false,
                "sampler_index": null,
                "sampler_steps": 20,
                "tilling": false,
                "seed": "-1",
                "vae_file": "auto",
                "clip_skip": 1,
            }
        }
        if(controlnetImg&&isImageUrl(controlnetImg)){
            var controlnetModel = 3//当basemode是1.5的时候mode是3，basemodel是xl时候，mode是97
            if(baseModel == "1.5"){
                controlnetModel = 3
            }else{
                controlnetModel = 97
            }
            controlNetParam = {
              "type": 3,//canny
              "preprocessor": 1,//canny
              "model": controlnetModel,//control_v11p_sd15_canny如果是1.5 model是3，如果是sdxl model值是97
              "control_weight": 1,
              "starting_control_step": 0,
              "ending_control_step": 1,
              "control_mode": 1,//0-平衡 1-prompt优先 2-control_net优先
              "image_url": controlnetImg,
              "processor_res": 512,
              "threshold_a": 171,
              "threshold_b": 255,
              "resize_mode": 2,//参考图缩放模式 0-仅调整大小 1-剪裁后缩放 2-缩放后填充空白
              "pixel_perfect": true
            };
            rawdata.control_net_params = controlNetParam
            hasControlNet = false
        }
        if(model_info!=null){
            if(model_info.model_fusion) {
                var option_param= {
                    "model_fusion": null
                }
                model_info.model_fusion = model_info.model_fusion.replace(/\\/g, "");
                model_fusion = JSON.parse(model_info.model_fusion)
                option_param.model_fusion = model_fusion
                rawdata.option_param = option_param;
            }
            
        }

        raw = JSON.stringify(rawdata); 
        
        
    }

    var requestOptions = {
        method: 'POST',
        headers: myHeaders,
        redirect: 'follow',
        body: raw
    };
    
    createImageing = true    
    const create_url = getCreateUrl()
    console.log("requset body:",raw)
    return fetch(create_url, requestOptions)
        .then(response => response.text())
        .then(result => {
            result = result.replace(/\\\\/g, '');// 去除字符串中的所有反斜杠
            console.log(result)
            const resultObject = JSON.parse(result);
            if (parseInt(resultObject.code) == 200 && resultObject.data.results) {
                const idArrays = resultObject.data.results.map(item => item.key);
                subtractPoints(idArrays.length)
                expectedSecondArray = resultObject.data.results.map(item => item.expected_second)
                return { idArray: idArrays, error: null };
            } else {
                var message = resultObject.message.replace('Midjourney','此模型')
                layer.msg(message, {icon: 1,time: 4000});  
                return { idArray: null, error: message };
            }
        })
        .catch(error => {
            console.log('error', error);
            createImageing = false
            return { idArray: null, error: error };
        });
        
}      

function subtractPoints(picNum){
    //调用接口更新points
    queryUrl = domain+'/api/user/public/subtractPoints?type=3&picNum='+picNum
    try {
            var requestOptions = {
                method: 'GET'
            };
            return fetch(queryUrl, requestOptions)
                .then(response => response.text())
                .then(result => {
                    console.log(result)
                })
                .catch(error => {
                    console.log('error', error);
                });
    
    } catch (error) {
        console.error(`Error fetching image data for id: ${key}`, error);
        return null;
    }  
}

   

function getWjAuth(){


    let timestamp = parseInt(Date.now()/1000).toString();
    let plaintText = JSON.stringify({
      appId: current_appid,//用于指定客户appID
      timestamp: timestamp
    })
    
    var auth = JSON.stringify({
        appId:current_appid,
        secretKeyVersion: "1",
        sign: sign(plaintText,current_sign_key),
        original:plaintText
    })
    return auth;
    

   
}    

function sign(plaintText,signkey) {
        
    var prk = forge.pki.privateKeyFromPem(signkey);
    const md = forge.md.sha256.create();
    md.update(plaintText, "utf8");
    var sign64 = prk.sign(md);
    var sign = forge.util.encode64(sign64)
    return sign;
}

async function wjGetHeader(){

    var auth = getWjAuth()
    var myHeaders = new Headers();
    myHeaders.append("User-Agent", "Apifox/1.0.0 (https://apifox.com)");
    myHeaders.append("Content-Type", "application/json");
    myHeaders.append("Authorization", auth);
    return myHeaders;
}



function wjAccelerate(keyArray,accelerateStep) {
    
    var auth = getWjAuth();
    
    
    for (let i = 0; i < keyArray.length; i++) {
            key = keyArray[i];
            var raw = JSON.stringify({
               "key": key,
               "auth": auth,
               "step_num": accelerateStep
            });
            
            var requestOptions = {
               method: 'POST',
               headers: {
                    'Content-Type': 'application/json',
                    'Authorization': auth
               },
               body: raw,
               redirect: 'follow'
            };
            
            fetch(api_acclerate_url, requestOptions)
               .then(response => response.text())
               .then(result => console.log(result))
               .catch(error => console.log('error', error));            
            
    }     
    
   
   
} 
function wjGetModelQueueInfo(modelCode) {
    var auth = getWjAuth();
    
    var requestOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': auth
        },
        redirect: 'follow'
    };
    
    return fetch(`https://gate.wujieai.net/wj-open/v1/ai/model_info?model=${modelCode}`, requestOptions)
        .then(response => response.json())
        .then(result => {
            console.log(result);
            return result; // 返回结果以便后续处理
        })
        .catch(error => {
            console.log('error', error);
            throw error; // 抛出错误以便调用者可以捕获
        });
}
async function autoAccelerateWhenNeeded(modelCode, keyArray) {
    
 
    try {
        // 1. 首先获取模型排队信息
        const queueInfo = await wjGetModelQueueInfo(modelCode);
        
        // 检查API响应是否成功
        if (queueInfo.code !== '200') {
            console.error('获取排队信息失败:', queueInfo.message);
            return;
        }
        
        const { queue_num, expected_seconds, reduce_time } = queueInfo.data;
        
        console.log(`当前排队信息: 任务数量=${queue_num}, 预计等待时间=${expected_seconds}秒, 每步减少时间=${reduce_time}秒`);
        
        // 2. 检查是否需要加速
        if (queue_num > 9 && expected_seconds > 15) {
            // 计算需要加速的步数
            //const stepsNeeded = Math.ceil((expected_seconds - 15) / reduce_time);
            //console.log(`需要加速 ${stepsNeeded} 步`);
            
            // 3. 调用加速函数
            wjAccelerate(keyArray, ACCELERATE_STEP);
            
            // 4. 再次检查排队信息（可能需要延迟一段时间后检查）
            setTimeout(async () => {
                const newQueueInfo = await wjGetModelQueueInfo(modelCode);
                console.log('加速后排队信息:', newQueueInfo.data);
            }, 2000); // 2秒后再次检查
        } else {
            console.log('当前无需加速');
        }
    } catch (error) {
        console.error('自动加速过程中出错:', error);
    }
}

async function updateAikey() {

    var requestOptions = {
       method: 'POST'
    };
    
    try {
            const response = await fetch(api_get_aikey, requestOptions);
            const responseObject = await response.json();
            
            console.log(responseObject['data'])
            
            if (responseObject['data']) {
                if (responseObject['data']['appid'] && responseObject['data']['sign_key']) {
                    current_appid = responseObject['data']['appid'];
                    current_sign_key = "-----BEGIN PRIVATE KEY-----\n"+responseObject['data']['sign_key']+"-----END PRIVATE KEY-----\n";
                }
    
            }
    } catch (error) {
        console.log(error)


    }    
  
  

} 
async function hmGetImageStatus(key) {
    var auth = getWjAuth();
    
    var raw = JSON.stringify({
        "key": key,
        "auth":auth
    });    
  
    var requestOptions = {
        method: 'POST',
        body: raw
    };
    const response = await fetch(api_get_aiimage_status_url, requestOptions)
    console.log("hmGetImageStatus response:",response)
    const responseObject = await response.json();
    
    updateWorksInfo(key,responseObject.data)
    if (parseInt(responseObject.code) == 200 && responseObject.data && responseObject.data.picture_url.length>0) {
        updateWorksInfo(key,responseObject.data)
        return responseObject.data.picture_url
    } else {
        
        return null;
    }
    

} 

async function hmGetImageGenerating_info(keys) {
    var auth = getWjAuth();
    var raw = JSON.stringify({
        "keys": keys,
        "auth":auth
    });   
    
  
    var requestOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: raw
    };
    
    try {
        const response = await fetch(api_get_aiimage_generatinginfo_url, requestOptions)
        const responseObject = await response.json();
        
        if (parseInt(responseObject.code) === 200 && responseObject.data && responseObject.data.infos) {

            // 解析每个图片的关键信息
            const results = responseObject.data.infos.map(info => {
                return {
                    key: info.key,
                    status: info.status, // 状态: 0-处理中, 1-已完成
                    complete_percent: info.complete_percent || 0, // 完成百分比
                    expected_seconds: info.expected_seconds || 0, // 预计剩余秒数
                    picture_url: info.picture_url || null, // 图片URL(完成时才有)
                    audit_info: info.audit_info || null, // 审核信息
                    fail_message: info.fail_message || null // 失败信息
                };
            });

            console.log('生成信息:', results);
            return results;

        } else {
            console.error('Error fetching generating info:', responseObject.message);
            return null;
        }
    } catch (error) {
        console.error('Network error:', error);
        return null;
    }
}


/*

function wjAnalysisPrompt(url) {
    var promptKey = null;
    var raw = JSON.stringify({
       "image_url": url,
       "notify_url": "www.reuben-mohr.net",
       "spell_analysis_model": 1//咒语解析器模型，1-默认，7-mj
    });
    
    var requestOptions = {
       method: 'POST',
       headers: wjGetHeader(),
       body: raw,
       redirect: 'follow'
    };
    var gettingPrompt = false
    var prompt = null
    fetch("https://gate.wujieai.net/wj-open/v1/ai/spell_analysis/create", requestOptions)
       .then(response => response.text())
       .then(result => {
            console.log(result)
            var jsonObject = JSON.parse(result);
            
            if (parseInt(jsonObject.code)== 200 && jsonObject.data.key) {
                promptKey = jsonObject.data.key
                console.log(promptKey)
                gettingPrompt = true
                
                startTime = new Date().getTime();
                const timer = setInterval(async () => {
                    const currentTime = new Date().toLocaleTimeString();
                    console.log(`Timer executed at: ${currentTime}`);
                    prompt = await wjGetPrompt(promptKey);
                    if(prompt != null){
                        console.log(prompt)
                        clearInterval(timer); 
                        var textarea = document.getElementById("input_textarea");
                        textarea.value = prompt;

                    }
            
                    // 检查是否超过30秒
                    const elapsedTime = new Date().getTime() - startTime;
                    if (elapsedTime >= 30000) {
                        clearInterval(timer); // 超过30秒后停止定时器
                        console.log("Timer stopped due to reaching the 60 seconds limit when getting prompt");
                        gettingPrompt = false
                    }
    
                }, 3000); // 每隔3秒执行一次       

            } else {
              console.log('error', jsonObject.message)
            }
        })
       .catch(error => console.log('error', error));
   
} */
/*
async function wjGetPrompt(promptKey) {
      

    var requestOptions = {
       method: 'GET',
       headers: wjGetHeader(),
       redirect: 'follow'
    };
    
 

  
    try {
        const response = await fetch("https://gate.wujieai.net/wj-open/v1/ai/spell_analysis/info?spellAnalysisKey="+promptKey, requestOptions)
        const responseObject = await response.json();
        
        if (parseInt(responseObject.code)== 200 && responseObject.data.tags) {
           console.log(responseObject.data.tags)
           return responseObject.data.tags
           
        } else {
          console.log('error', responseObject.message)
          return null
        }
    
    } catch (error) {
        console.error(`error`, error);
        return null;
    }         
 
} */
/*function generateRandomString(length) {
  var result = '';
  var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var charactersLength = characters.length;

  for (var i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }

  return result;
}*/

/*
async function wjOptimizePrompt(prompot) {
      

  var raw = JSON.stringify({
        "task_id": generateRandomString(18),
        "type": 1,
        "original": prompot,
        "language": 0,
        "callback_url": null
    });
 

    
    var requestOptions = {
        method: 'POST',
        headers: wjGetHeader(),
        body: raw,
        redirect: 'follow'
    };
    
    createImageing = true    
    return fetch("https://gate.wujieai.net/wj-open/v1/ai/optimize/prompt/submit", requestOptions)
        .then(response => response.text())
        .then(result => {
            console.log(result)
            
            const resultObject = JSON.parse(result);

        })
        .catch(error => {
            console.log('error', error);
            return null
        });       
 
} */

/* function analysisPrompt() {
  
      var image_url = $(".graph-d20-search-wrapper-input").val();
      image_url = upload_imgage_url_prefix+image_url
      console.log(image_url)
      wjAnalysisPrompt(image_url)
}  */ 

function autoDesign(){
      var image_url = $(".graph-d20-search-wrapper-input").val();
      image_url = upload_imgage_url_prefix+image_url
      var index = layer.open({
        type: 2,
        title: '自动设计',
        title:false,
        scrollbar:false,
        maxmin: false,
        closeBtn:0,
        //  shade: [0.8, '#393D49'],
        // shadeClose:true,
/*        shade: true,
        shade: [0.5],
        shadeClose: true,*/
        skin: 'myskin',
        anim: 2,
        area: ['1000px','1000px'],
        offset: '50px',
        
            content: [domain+'/portal/searchimage/aiimage?image_url='+image_url, 'no'],
        
        });
        console.log(index)   

}
function doodleDesign(){
      var image_url = $(".graph-d20-search-wrapper-input").val();
      image_url = upload_imgage_url_prefix+image_url
      var index = layer.open({
        type: 2,
        title: '涂鸦设计',
        title:false,
        scrollbar:false,
        maxmin: false,
        closeBtn:0,
        //  shade: [0.8, '#393D49'],
        // shadeClose:true,
/*        shade: true,
        shade: [0.5],
        shadeClose: true,*/
        skin: 'myskin',
        anim: 2,
        area: ['1000px','1000px'],
        offset: '50px',
        
            content: [domain+'/portal/searchimage/doodledesign?image_url='+image_url, 'no'],
        
        });
        console.log(index)   

}


async function chatDesign(modelName,designNum) {
    const machine_prompt = '给我推荐'+designNum+'个'+modelName+'的设计，要符合'+modelName+'的颜色、材质和特性，给出设计名字，设计描述和象征意义。设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。返回格式为可解析的json。json中每个item的格式为{"name": 设计名字,"desc": 设计描述,"meaning": 象征意义}'
    const columnWidths = ['50px', '300px', '100px'];
    const columnNames = ['名称', '描述', '意义'];
    const gptPromptKeys = ['name', 'desc','meaning'];
    const promptColumnIndex = 1
    chatDesignLoading.style.display = 'flex'
    await autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,promptColumnIndex,gptPromptKeys)   
    chatDesignLoading.style.display = 'none'
    
}


async function getPoemSentence(category) {
    apiURL = 'https://open.saintic.com/api/sentence/';
    if(poemMode=="fast"){
        apiURL = domain+'/api/portal/aiimage/getPoemSentence?category='+category; 
    }

  try {
    // 使用fetch API发送GET请求并等待响应
    const response = await fetch(apiURL);

    // 确保响应状态为成功
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    // 解析JSON数据并等待结果
    const data = await response.json();
    if(poemMode=="kimi"){
        // 检查返回的数据格式是否正确
        if (data && data.data && data.data.name) {
          // 提取sentence字段的值
          const sentence = data.data.sentence;
          console.log('古诗句子:', sentence); // 打印古诗句子
          return sentence; // 返回古诗句子
        } else {
          throw new Error('Data format is incorrect or missing expected fields');
        }        
    }else{
        // 检查返回的数据格式是否正确
        if (data && data.data && data.data.list) {
            
          // 提取sentence字段的值
          const sentence = data.data.list.poem;
          console.log('古诗句子:', sentence); // 打印古诗句子
          return sentence; // 返回古诗句子
        } else {
          throw new Error('Data format is incorrect or missing expected fields');
        }        
    }


  } catch (error) {
    console.error('There has been a problem with your fetch operation:', error);
  }
 

  
}




async function poemDesign(modelName,designNum) {
    var columnWidths = ['100px', '300px', '100px'];
    var columnNames = ['作品名','描述','设计理念'];
    var gptPromptKeys = ['name','desc','meaning']; 
    var machine_prompt = null;
    
    
    
    
    // 获取用于插入表格的容器
    const tableContainer = document.getElementById('table-container');
    tableContainer.innerHTML = '';
    
    // 创建div
    const gptInput = document.createElement('div');
    gptInput.id = 'gptInput';
    tableContainer.appendChild(gptInput);

    // 创建文本框
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = '填写内容';
    input.style.height = '30px';
    input.style.fontSize = '22px';
    input.style.width = '600px';
    gptInput.appendChild(input);
    
    
    const changeBtn = document.createElement('button');
    changeBtn.className = 'gptBtn'; 
    changeBtn.innerHTML = '换一句';
    gptInput.appendChild(changeBtn);    
    changeBtn.addEventListener('click', async function() {
        poem = await getPoemSentence(modelName)
        input.value = poem
/*        columnWidths = ['50px', '300px', '100px'];
        columnNames = ['设计作品名','设计描述','设计理念'];
        gptPromptKeys = ['name','desc','meaning'];
        
        chatDesignLoading.style.display = 'flex'
        machine_prompt = '根据诗句“'+poem+',给我推荐'+designNum+'个'+modelName+'的设计，要求如下：'+
                '1、要符合诗句的描述,并且符合'+modelName+'的颜色、材质和特性'+
                '2、结合诗句给出设计的设计名字、设计描述和象征意义。'+
                '3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。'+
                '4、返回格式为可解析的json。json中每个item的格式为{"name": 设计名字,"desc": 设计描述,"meaning": 象征意义}。'
        await autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,1,gptPromptKeys) 
        chatDesignLoading.style.display = 'none'*/
    });     
    

    // 创建按钮
    const generateBtn = document.createElement('button');
    generateBtn.innerHTML = '诗文成艺';
    generateBtn.className = 'gptBtn'; 
    gptInput.appendChild(generateBtn);
    generateBtn.addEventListener('click', async function() {
        columnWidths = ['100px', '300px', '100px'];
        columnNames = ['作品名','描述','设计理念'];
        gptPromptKeys = ['name','desc','meaning'];
        chatDesignLoading.style.display = 'flex'
        poem = input.value;
        machine_prompt = '根据诗句“'+poem+',给我推荐'+designNum+'个'+modelName+'的设计，要求如下：'+
            '1、要符合诗句的描述,并且符合'+modelName+'的颜色、材质和特性'+
            '2、结合诗句给出设计的设计名字、设计描述和象征意义。'+
            '3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。'+
            '4、返回格式为可解析的json。json中每个item的格式为{"name": 设计名字,"desc": 设计描述,"meaning": 象征意义}。'  
        if(poemMode=="kimi"){
            await autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,1,gptPromptKeys) 
        }else{
            await createPoemDesignTable(modelName,input.value,columnWidths,columnNames,1,gptPromptKeys) 
        }   
            
    
        chatDesignLoading.style.display = 'none'
    });    
    
    

    const autoBtn = document.createElement('button');
    autoBtn.className = 'gptBtn'; 
    autoBtn.innerHTML = '诗海撷珍';
    gptInput.appendChild(autoBtn);
    autoBtn.addEventListener('click', async function() {
        chatDesignLoading.style.display = 'flex'
        columnWidths = ['50px', '100px', '100px', '300px', '100px'];
        columnNames = ['诗名', '诗句', '设计作品名','设计描述','设计理念'];
        gptPromptKeys = ['poemname',  'poem','name','desc','meaning']; 
        machine_prompt = '给我推荐'+designNum+'个'+modelName+'的设计，要求如下：'+
                '1、设计的出处是中国古代诗词，给出诗词语句和诗词出处。'+
                '2、结合诗词给出设计的设计名字、设计描述和象征意义。'+
                '3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。返回格式为可解析的json。json中每个item的格式为{“poemname”:出处,"poem":诗词语句,name": 设计名字,"desc": 设计描述,"meaning": 象征意义}。'+
                '4、要符合'+modelName+'颜色、材质和特性'+
                '5、要给我推荐和之前出处不一样的诗句'
        if(poemMode=="kimi"){
            await autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,3,gptPromptKeys) 
        }else{
            await createPoemDesignTable(modelName,input.value,columnWidths,columnNames,3,gptPromptKeys,"poemsea") 
        }  
        
        chatDesignLoading.style.display = 'none'
    });
    
   
    
    
    
    document.getElementById('listoverlay').classList.add('active');
    
    //第一次创建，只有给定的诗词，所以只有作品名、描述、设计理念
    chatDesignLoading.style.display = 'flex'
    poem = await getPoemSentence(modelName)
    input.value = poem
/*    machine_prompt = '根据诗句“'+poem+',给我推荐'+designNum+'个'+modelName+'的设计，要求如下：'+
            '1、要符合诗句的描述,并且符合'+modelName+'的颜色、材质和特性'+
            '2、结合诗句给出设计的设计名字、设计描述和象征意义。'+
            '3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。'+
            '4、返回格式为可解析的json。json中每个item的格式为{"name": 设计名字,"desc": 设计描述,"meaning": 象征意义}。'
    columnWidths = ['50px', '300px', '100px'];
    columnNames = ['设计作品名','设计描述','设计理念'];
    gptPromptKeys = ['name','desc','meaning'];
    await autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,1,gptPromptKeys)*/
    chatDesignLoading.style.display = 'none'
    
    
}

async function getBookExtract(fileId,modelName){

    try {    
            var xhr = new XMLHttpRequest();
            xhr.open('GET', `https://api.moonshot.cn/v1/files/${fileId}/content`, true);
            
            // 设置请求头部信息
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("Authorization", `Bearer ${kimiapiKey}`);
            
            // 发送请求
            xhr.send();
            // 等待请求完成
            await new Promise((resolve, reject) => {
              xhr.addEventListener('load', resolve);
              xhr.addEventListener('error', reject);
            });
        // 请求成功，解析响应内容
            if (xhr.status === 200) {
              var response = JSON.parse(xhr.responseText);
              var content = response.content;
              return content
        } else {
              console.log('获取文件内容失败');
              return null;
        }
    }catch (error) {
            console.log('请求出错');
            return null;
    }
}
async function kimiTranslate(sentence){

    //const apiUrl = "https://api.moonshot.cn/v1/chat/completions";
    const apiUrl = api_moonshot_url;
    var requestData = null;

    requestData = {
      model: "moonshot-v1-32k",
      messages: [
        { role: "system", content: "你是一个专业的英文翻译，你会将我给你的语句精准的用英文翻译出来，但不改变标点符号和单词顺序" },
        { role: "user", content: sentence }
      ],
      max_tokens:20000,
      temperature: 0.3
    };        




    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${kimiapiKey}`
            },
            body: JSON.stringify(requestData)
        });
        if (!response.ok) {
            console.log(response.status)
            const data = await response.json();
            console.log(data)
            layer.msg(data.error.message, {icon: 1,time: 5000});     
            chatDesignLoading.style.display='none'
            
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        const completion = data.choices[0].message;
        //有的时候gpt返回的数据有除了json以外的数据，造成无法解析，需要提取出来
        var translatedSentence = completion.content.replace(/`/g, '').replace(/json/g, ''); 
        return translatedSentence; // 确保返回解析后的值
    } catch (error) {
        console.error("Error in kimiTranslate:", error);
        return null;
    }
}

async function kimiOptimize(prompt){

    const apiUrl = "https://api.moonshot.cn/v1/chat/completions";
    var requestData = null;

    requestData = {
      model: "moonshot-v1-32k",
      messages: [
        { role: "system", content: "Stable Diffusion是一款利用深度学习的文生图模型，支持通过使用提示词来产生新的图像，描述要包含或省略的元素。我在这里引入Stable Diffusion算法中的Prompt概念，又被称为提示符。这里的Prompt通常可以用来描述图像，他由普通常见的词语构成，最好是可以在数据集来源站点找到的著名标签（比如Ddanbooru）。下面我将说明Prompt的生出步骤，这里的Prompt主要用于描述中国工艺美术作品。在Prompt的生成中，你需要通过提示词来描述作品的造型、材质、纹样、颜色、视角、背景。用词语来描述，并不局限于我给你的词语。然后将你想要的相似的提示词组合在一起，请使用半角逗号,做分隔符，每个提示词不要带引号，并将这些按造型、材质、纹样、颜色、视角、背景的顺序排列。另外再注意，Prompt中不能带有-和_，整个prompt在10个到15个词语之间，可以有空格和自然语言，但不要太多，词语不能重复，不能是英文，直接给出词语，不用前缀或者引导语。" },
        { role: "user", content: "请根据以上原则优化如下prompt："+prompt}
      ],
      max_tokens:20000,
      temperature: 0.3
    };        




    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${kimiapiKey}`
            },
            body: JSON.stringify(requestData)
        });
        if (!response.ok) {
            console.log(response.status)
            const data = await response.json();
            console.log(data)
            layer.msg(data.error.message, {icon: 1,time: 5000});     
            chatDesignLoading.style.display='none'
            
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        const completion = data.choices[0].message;
        //有的时候gpt返回的数据有除了json以外的数据，造成无法解析，需要提取出来
        var promptOptimized = completion.content; 
        return promptOptimized; // 确保返回解析后的值
    } catch (error) {
        console.error("Error in kimiTranslate:", error);
        return null;
    }
}
async function bookDesign(modelName,designNum) {
    var machine_prompt = null;
    var columnWidths = ['100px', '50px', '300px', '100px'];
    var columnNames = ['古籍描述',  '作品名','描述','设计理念'];
    var gptPromptKeys = ['origin',  'name','desc','meaning'];
    var promptColumnIndex = 2;


    chatDesignLoading.style.display = 'none' 
    
    // 获取用于插入表格的容器
    const tableContainer = document.getElementById('table-container');
    tableContainer.innerHTML = '';
    
  
    
    // 创建div
    const gptInput = document.createElement('div');
    gptInput.id = 'gptInput';
    tableContainer.appendChild(gptInput);
    
    
    var textNode = document.createTextNode("请选择典籍来源：");
    gptInput.appendChild(textNode);
    
    var bookselectDiv = document.createElement('div');
    gptInput.appendChild(bookselectDiv);
    
    var inputElement = document.createElement('input');
    inputElement.type = 'hidden';
    inputElement.id = 'bookattach';
    gptInput.appendChild(inputElement);
    
    var textNode = document.createTextNode("或上传典籍：");
    gptInput.appendChild(textNode);
    
    const uploadBtn = document.createElement('button');
    uploadBtn.innerHTML = '上传文件';
    uploadBtn.className = 'gptBtn'; 
    uploadBtn.addEventListener('click', async function() {
        console.log("sasdf")
   /*     uploadOne('文件上传','#bookattach','file')*/
             // 创建一个FormData对象
            var formData = new FormData();
            
            // 创建一个input元素
            var fileInput = document.createElement('input');
            fileInput.type = 'file';
            
            // 监听文件选择事件
            fileInput.addEventListener('change', function(event) {
              var file = event.target.files[0];
              formData.append('file', file);
            
              // 创建XMLHttpRequest对象
              var xhr = new XMLHttpRequest();
            
              // 设置请求地址和方法
              
              xhr.open('POST', 'https://api.moonshot.cn/v1/files', true);
              xhr.setRequestHeader("Authorization", `Bearer ${kimiapiKey}`);
            
              // 发送FormData对象
              xhr.send(formData);
            
              // 监听上传进度
              xhr.upload.addEventListener('progress', function(event) {
                if (event.lengthComputable) {
                  var percentComplete = (event.loaded / event.total) * 100;
                  console.log('上传进度：' + percentComplete + '%');
                }
              });
            
              // 监听上传完成事件
              xhr.addEventListener('load', async function(event) {
                if (xhr.status === 200) {
                  console.log('文件上传成功');
                  // 在这里处理上传成功后的逻辑
                    var data = JSON.parse(xhr.responseText);
                    var fileId = data.id;
                    console.log("fileId："+fileId)
                    const fileContent = await getBookExtract(fileId)
                  // 构建机器提示
                  const machine_prompt = `结合给定文件，给我推荐${designNum}个${modelName}的设计，要求如下：
                  1、设计的出处是文件里描写${modelName}的语句，给出出处语句的原文。      2、结合出处语句原文，给出设计的设计名字和设计描述。      3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。返回格式为可解析的json。json中每个item的格式为{"origin":出处语句,"name":设计名字,"desc":设计描述,"meaning":象征意义}。      4、要符合${modelName}颜色、材质和特性`;
            
                  // 显示加载提示
                  chatDesignLoading.style.display = 'flex';
            
                  // 调用autoGptDesign函数处理设计
                  const designResults = await autoGptDesign(machine_prompt, modelName, designNum, columnWidths, columnNames, promptColumnIndex, gptPromptKeys, fileContent);
            
                  // 隐藏加载提示
                  chatDesignLoading.style.display = 'none';
                } else {
                  console.log('文件上传失败');
                  // 在这里处理上传失败后的逻辑
                }
              });
            
              // 监听上传错误事件
              xhr.addEventListener('error', function(event) {
                console.log('文件上传出错');
                // 在这里处理上传出错后的逻辑
              });
            });
            
            // 触发文件选择对话框
            fileInput.click();

        
        
        
      
    });;
    gptInput.appendChild(uploadBtn);    
    
    
    // 创建<select>元素
    var selectElement = document.createElement('select');
    selectElement.style.height = '40px'
    selectElement.style.fontSize = '20px';
    selectElement.style.textAlign = 'center';
    
    selectElement.id = 'bookselect';
    selectElement.name = 'bookselect';
    const ancientChineseNovels = ["","《红楼梦》", "《西游记》","《水浒传》","《三国演义》"];

    // 使用map函数转换数组格式
    var optionsArray = ancientChineseNovels.map(novel => {
      return {
        value: novel, // 使用小说名称作为value
        text: novel // 使用小说名称作为text
      };
    });
    
    // 遍历选项数组，创建并添加<option>元素
    optionsArray.forEach(function(option) {
        var optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        selectElement.appendChild(optionElement);
    });
    // 将<select>元素添加到div中
    bookselectDiv.appendChild(selectElement);      
    selectElement.addEventListener('change', async function() {
        const selectedIndex = selectElement.selectedIndex;
        const selectedOption = selectElement.options[selectedIndex];
        const bookname = selectedOption.text;
        
        console.log('选中的小说名称：', bookname);
        if(bookname.length > 0){
/*            machine_prompt = '给我推荐'+designNum+'个'+modelName+'的设计，要求如下：'+
                '1、设计的出处是中国名著'+bookname+'里描写'+modelName+'的语句，给出出处语句的原文。'+
                '2、结合出处语句原文，给出设计的设计名字和设计描述。'+
                '3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。返回格式为可解析的json。json中每个item的格式为{“origin”:出处语句,name": 设计名字,"desc": 设计描述,"meaning": 象征意义}。'+
                '4、要符合'+modelName+'颜色、材质和特性' */     
            chatDesignLoading.style.display = 'flex'
            /*await autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,promptColumnIndex,gptPromptKeys) */
            await createBookDesignTable(modelName,bookname,designNum,columnWidths,columnNames,promptColumnIndex,gptPromptKeys) 
            
            
            chatDesignLoading.style.display = 'none'   
        }else{
            layer.msg('请选择一本典籍', {icon: 1,time: 2000});
        }
      
    });
    
    document.getElementById('listoverlay').classList.add('active');
  
}

function createTableHead(tableContainer,table,columnWidths,columnNames){

    // 创建表头
    const thead = document.createElement('thead');
    const trHead = document.createElement('tr');
    columnNames.forEach((thText, index) => {
        const th = document.createElement('th');
        th.textContent = thText;
        th.style.width = columnWidths[index];
        trHead.appendChild(th);
    });
    thead.appendChild(trHead);
    table.appendChild(thead);
    
}
function createTableBody(tableContainer,table,designPrompts,columnWidths,gptPromptKeys,promptColumnIndex){

  
    var alreadyHasTableBody = false
    const tableBody = document.createElement('tbody');
    tableBody.classList.add('tablebody');
    tableBody.id ='tableBody'
    
    designPrompts.forEach((design, index) => {
        const tr = document.createElement('tr');
        tr.classList.add('tabletr');
        gptPromptKeys.forEach((key, columnIndex) => {
            const td = document.createElement('td');
            td.textContent = design[key];
            td.style.width = columnWidths[columnIndex];
            tr.appendChild(td);       
        });
 
        tableBody.appendChild(tr);
    });
    table.appendChild(tableBody);
    
    
    
    // 将表格添加到容器中
    tableContainer.appendChild(table);    
    document.getElementById('listoverlay').classList.add('active');
    var rows = tableContainer.getElementsByTagName("tr");
    for (var i = 0; i < rows.length; i++) {
      rows[i].addEventListener("mouseover", function() {
        this.classList.add("highlight");
      });
    
      rows[i].addEventListener("mouseout", function() {
        this.classList.remove("highlight");
      });
      
      rows[i].addEventListener("click", function() {
          const cells = this.getElementsByTagName("td");
          const secondColumnContent = cells[promptColumnIndex].textContent || cells[promptColumnIndex].innerText;
          console.log(secondColumnContent); // 打印第二列的内容        
          input_textarea.value = secondColumnContent
          //诗文和古籍成艺默认6张
          count_input.value = 6
          startCreateImg()
          document.getElementById('listoverlay').classList.remove('active');
      });
      
    }    
}
async function autoGptDesign(machine_prompt,modelName,designNum,columnWidths,columnNames,promptColumnIndex,gptPromptKeys,fileContent = null) {
    designPrompts = await getGptPrompt(modelName,designNum,machine_prompt,fileContent);
    console.log(designPrompts)
    
    // 获取用于插入表格的容器
    const tableContainer = document.getElementById('table-container');
     // 创建表格元素
    var gptTable = document.getElementById('gptTable');
    if(gptTable==null){
        gptTable = document.createElement('table');
        gptTable.id = 'gptTable'
        gptTable.style.border = '1px solid black'; // 可选：为表格添加边框   
    }else{
        gptTable.innerHTML = ''
    }
    //创建表头
    createTableHead(tableContainer,gptTable,columnWidths,columnNames)
    // 创建表体
    createTableBody(tableContainer,gptTable,designPrompts,columnWidths,gptPromptKeys,promptColumnIndex)
    
}
async function createBookDesignTable(modelName,bookname,designNum,columnWidths,columnNames,promptColumnIndex,gptPromptKeys) {
    designPrompts = await getBookDesign(modelName,bookname);
    console.log(designPrompts)
    
    // 获取用于插入表格的容器
    const tableContainer = document.getElementById('table-container');
     // 创建表格元素
    var gptTable = document.getElementById('gptTable');
    if(gptTable==null){
        gptTable = document.createElement('table');
        gptTable.id = 'gptTable'
        gptTable.style.border = '1px solid black'; // 可选：为表格添加边框   
    }else{
        gptTable.innerHTML = ''
    }
    //创建表头
    createTableHead(tableContainer,gptTable,columnWidths,columnNames)
    // 创建表体
    createTableBody(tableContainer,gptTable,designPrompts,columnWidths,gptPromptKeys,promptColumnIndex)
    
}
async function getBookDesign(modelName,book) {
    
  const url = domain+'/api/portal/aiimage/getBookDesign?category='+modelName+"&book="+book;
  
  try {
    const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        });
        const data = await response.json();
        if (data.code === 1 && data.data.list.length) {
          return data.data.list;
        } else {
          return null;
        }
  } catch (error) {
    console.error(`Error fetching bookdesign info  for id: ${modelName}`, error);
    return null;
  }    
    
}
async function createPoemDesignTable(modelName,poem,columnWidths,columnNames,promptColumnIndex,gptPromptKeys,poemMode="siglepoem") {
    designPrompts = await hmGetPoemDesign(modelName,poem,poemMode);
    if(!designPrompts){
        machine_prompt = '根据诗句“'+poem+',给我推荐'+designNumPerPoem+'个'+modelName+'的设计，要求如下：'+
        '1、要符合诗句的描述,并且符合'+modelName+'的颜色、材质和特性'+
        '2、结合诗句给出设计的设计名字、设计描述和象征意义。'+
        '3、设计描述首先给出器型，然后尽可能的像设计稿一样给出具体描述。'+
        '4、返回格式为可解析的json。json中每个item的格式为{"name": 设计名字,"desc": 设计描述,"meaning": 象征意义}。'  
        designPrompts = await getGptPrompt(modelName,designNumPerPoem,machine_prompt);
    }
    console.log(designPrompts)
  
            
            
   
  
    
    // 获取用于插入表格的容器
    const tableContainer = document.getElementById('table-container');
     // 创建表格元素
    var gptTable = document.getElementById('gptTable');
    if(gptTable==null){
        gptTable = document.createElement('table');
        gptTable.id = 'gptTable'
        gptTable.style.border = '1px solid black'; // 可选：为表格添加边框   
    }else{
        gptTable.innerHTML = ''
    }
    //创建表头
    createTableHead(tableContainer,gptTable,columnWidths,columnNames)
    // 创建表体
    createTableBody(tableContainer,gptTable,designPrompts,columnWidths,gptPromptKeys,promptColumnIndex)
    
}



//从本机数据库中根据诗句返回json格式的自动设计prompts 
async function hmGetPoemDesign(modelName,poem,poemMode) {
    
  const url = domain+'/api/portal/aiimage/getPoemDesign?category='+modelName+"&poem="+poem+'&poemmode='+poemMode;
  
  try {
    const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
          }
        });
        const data = await response.json();
        console.log(data)
        if (data.code === 1 && data.data.list.length) {
          return data.data.list;
        } else {
 
          return null;
        }
  } catch (error) {
    console.error(`Error fetching poemdesign info  for id: ${modelName}`, error);
    return null;
  }    
    
}
async function getGptPrompt(modelName,designNum,machine_prompt,fileContent=null){

 
    const apiUrl = "https://api.moonshot.cn/v1/chat/completions";
    var requestData = null;
    if(fileContent){
        //去除非中文字符
        const regex = new RegExp(/[^\u4e00-\u9fa5]/gu);
        fileContent = fileContent.replace(regex, '');
        fileContent = fileContent.slice(0, 128000);
        requestData = {
          model: "moonshot-v1-128k",
          messages: [
            { role: "system", content: "你是 Kimi，由 Moonshot AI 提供的人工智能助手，你更擅长中文和英文的对话。你会为用户提供安全，有帮助，准确的回答。同时，你会拒绝一切涉及恐怖主义，种族歧视，黄色暴力等问题的回答。Moonshot AI 为专有名词，不可翻译成其他语言。" },
            { role: "user", content: fileContent },
            { role: "user", content: machine_prompt}
          ],
          max_tokens:20000,
          temperature: 0.3
        };        
    }else{
        requestData = {
          model: "moonshot-v1-128k",
          messages: [
            { role: "system", content: "你是 Kimi，由 Moonshot AI 提供的人工智能助手，你更擅长中文和英文的对话。你会为用户提供安全，有帮助，准确的回答。同时，你会拒绝一切涉及恐怖主义，种族歧视，黄色暴力等问题的回答。Moonshot AI 为专有名词，不可翻译成其他语言。" },
            { role: "user", content: machine_prompt }
          ],
          max_tokens:20000,
          temperature: 0.3
        };        
    }



    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${kimiapiKey}`
            },
            body: JSON.stringify(requestData)
        });
        if (!response.ok) {
            console.log(response.status)
            const data = await response.json();
            console.log(data)
            layer.msg(data.error.message, {icon: 1,time: 5000});     
            chatDesignLoading.style.display='none'
            
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        const completion = data.choices[0].message;
        //有的时候gpt返回的数据有除了json以外的数据，造成无法解析，需要提取出来
        var designPrompts = completion.content.replace(/`/g, '').replace(/json/g, ''); 
        // 定义正则表达式，用于匹配JSON数据
        const regex = /\[[^\]]*\]/g;
        // 使用正则表达式提取JSON数据
        designPrompts = designPrompts.match(regex);
        console.log(designPrompts)
        const parsedDesignPrompts = JSON.parse(designPrompts);
        return parsedDesignPrompts; // 确保返回解析后的值
    } catch (error) {
        console.error("Error in getDesignPrompt:", error);
        return null;
    }
   
}

   
   