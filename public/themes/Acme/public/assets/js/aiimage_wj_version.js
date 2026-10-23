
const input_textarea = document.getElementById('input_textarea')
const detail_clean = document.getElementById('detail_clean')
const now_text_num = document.getElementById('now_text_num')
const size_each = [...document.getElementsByClassName('size-each')]
const near_prev = document.getElementById('near_prev')
const near_input = document.getElementById('near_input')
const near_next = document.getElementById('near_next')
const count_prev = document.getElementById('count_prev')
const count_input = document.getElementById('count_input')
const count_next = document.getElementById('count_next')
const submit_btn = document.getElementById('submit_btn')
const img_container = document.getElementById('img_container')
var chatDesignLoading = document.getElementById('chat-design-loading');
var firstCreate = true
var expectedSecondArray = null
var createImageing = false;
var platformId = 1//0为6pen，1为wj
var creatingTimer = 0
var wjIsPro = true
var baseModelId = 0
const listOverlay = document.getElementById('listoverlay');
const everything = document.getElementById('everything');
const everything2art = document.getElementById('everything2art');
const tableContainer = document.getElementById('table-container');
var kimiapiKey = "REMOVED_SECRET";
var hasControlNet = false
var refImgElement = document.querySelector('.ref_image_url');
var refImgWidth = 0;
var refImgHeight = 0;
var controlnetImg = null
var poemMode = "fast"  //kimi为从kimi获取设计,fast为从预置诗词库获取
var startTime = 0;
var hostname = window.location.hostname;
var domain = "https://www."+hostname.split('.').slice(-2).join('.');



// 如果图片尚未加载完成，可以监听load事件
refImgElement.onload = function() {
    var refImgWidth = this.naturalWidth;
    var refImgHeight = this.naturalHeight;
    console.log('图片的宽度是：' + refImgWidth + 'px');
    console.log('图片的高度是：' + refImgHeight + 'px');
}

// 添加点击事件监听器到listoverlay
listOverlay.addEventListener('click', function(event) {
    // 检查点击事件的目标是否是table-container或其子元素
    if (event.target !== tableContainer && !tableContainer.contains(event.target)) {
        // 如果不是，则移除listoverlay的active类
        listOverlay.classList.remove('active');
    }
})
// 添加点击事件监听器到listoverlay
everything.addEventListener('click', function(event) {
    // 检查点击事件的目标是否是table-container或其子元素
    if (event.target !== everything2art && !everything2art.contains(event.target)) {
        // 如果不是，则移除listoverlay的active类
        everything.classList.remove('active');
    }
})
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
    
   // 使用querySelectorAll选择器获取所有具有selected类的元素
    var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
    var modelId = null
    selectedElements.forEach(function(element) {
      modelId = element.id;
    });
    if(!modelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
         var model_info = await getModelInfo(modelId);
         input_textarea.value = model_info.default_prompt
         console.log(model_info.default_prompt)
    }
    
})
prompt_random.addEventListener('click', async function (e) {
    

    var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
    var modelId = null
    selectedElements.forEach(function(element) {
      modelId = element.id;
    });
    if(!modelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        promptArray = await getModelPrompt(modelId)
        // 使用 Math.random 和 Math.floor 来获取随机索引
        let randomIndex = Math.floor(Math.random() * promptArray.length);
        
        // 使用随机索引来访问并获取随机对象
        let randomPromptObject = promptArray[randomIndex];
        input_textarea.value = randomPromptObject.pre_prompt+randomPromptObject.tag_prompt
    }    
    
    
    
})

function getRefImage(){
    var refImgUrl = $(".ref_image_url").val();
    if (refImgUrl) {
        document.getElementById("ref-img-row").style.display = "flex";
        document.getElementById("dashed-box").style.display = "none";
        document.querySelector(".ref-img-container img").src = image_url = "https://www.hongmengyishu.com/upload/"+refImgUrl;
    } else {
        document.getElementById("ref-img-row").style.display = "none";
        document.getElementById("dashed-box").style.display = "flex";
    }
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
  const url = `https://www.hongmengyishu.com/api/portal/aiimage/getEverything?category=`+category;
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

    var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
    var modelId = null
    var modelName = null
    selectedElements.forEach(function(element) {
      modelId = element.id;
      var textMaskElement = element.querySelector('.text-mask');
      modelName = textMaskElement.textContent.replace(/\s/g, "");
      // 删除空格、回车和换行符
      console.log(modelName)
      
 
    });
  
     if(!modelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        chatDesignLoading.style.display = 'flex'
        chatDesign(modelName,8)
    }    
}
function poemToArt() {
    var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
    var modelId = null
    var modelName = null
    selectedElements.forEach(function(element) {
      modelId = element.id;
      var textMaskElement = element.querySelector('.text-mask');
      modelName = textMaskElement.textContent.replace(/\s/g, "");
      // 删除空格、回车和换行符
      console.log(modelName)
      
 
    });
  
     if(!modelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        chatDesignLoading.style.display = 'flex'
        poemDesign(modelName,5)
    }    
}

function storyToArt() {

    var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
    var modelId = null
    var modelName = null
    selectedElements.forEach(function(element) {
      modelId = element.id;
      var textMaskElement = element.querySelector('.text-mask');
      modelName = textMaskElement.textContent.replace(/\s/g, "");
      // 删除空格、回车和换行符
      console.log(modelName)
      
 
    });
  
     if(!modelId){
         layer.msg('请选择一个模型', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
        return;       
    }else{
        chatDesignLoading.style.display = 'flex'
        bookDesign(modelName,10)
    }     
}

function everythingToArt() {

    var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
    var modelId = null
    var modelName = null
    selectedElements.forEach(function(element) {
      modelId = element.id;
      var textMaskElement = element.querySelector('.text-mask');
      modelName = textMaskElement.textContent.replace(/\s/g, "");
      // 删除空格、回车和换行符
      console.log(modelName)
      
 
    });
  
     if(!modelId){
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



function everythingDesign() {
    const selectedItem = document.querySelector('.item.selected');
    if (selectedItem) {
        const imgElement = selectedItem.querySelector('.img002');
        prompt = document.getElementById('everythingPrompt').value
        if(prompt.length<=3){
            layer.msg('请输入四个字以上的提示语', {icon: 1,time: 2000});
        }else{
            hasControlNet = true
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
function styleShift(){
 
    layer.msg('此功能正在研发中....', {
    	  icon: 1,
    	  time: 2000 //2秒关闭
    	});
}

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

near_prev.addEventListener('click', function (e) {
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
})
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
                        startCreateImg()
                    }, function(index){
                        layer.close(index); 
                    });
                    
                }else{
                    startCreateImg()
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
function startCreateImg(){
        const prompt = input_textarea.value
        const near_input_value = near_input.value
        const creatImageNum = count_input.value
        console.log(prompt, near_input_value, creatImageNum);
        if(!hasControlNet&&prompt.length <=4 ){
            //有controlnet的话，根据图片直接生成，不用描述语
           layer.msg('请输入4个汉字以上的图像描述语', {
                	  icon: 1,
                	  time: 2000 //2秒关闭
                	});
            return;
        }
        

        // 使用querySelectorAll选择器获取所有具有selected类的元素
        var selectedElements = document.querySelectorAll('.aiimge-slect-box.selected');
        var modelId = null
        selectedElements.forEach(function(element) {
          modelId = element.id;
        });
        

        if(!modelId){
             layer.msg('请选择一个模型', {
                	  icon: 1,
                	  time: 2000 //2秒关闭
                	});
            return;       
        }else{
             wjSetInterfaceVersion()
             createImgGrid(creatImageNum);
             createImages(prompt, baseModelId,modelId, creatImageNum)
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

function printImageUrls() {
  const imgContainer = document.getElementById('img_container');
  const images = imgContainer.getElementsByTagName('img');

  for (let i = 0; i < images.length; i++) {
    const imageUrl = images[i].getAttribute('src');
    console.log(imageUrl);
  }
}

function createImgGrid(imageNum) {
    img_container.innerHTML = ''; // 清空img_container下的所有子节点
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
    console.log(imageNumPerLine,lines)
    img_container.style = ` grid-template-columns: repeat(${imageNumPerLine}, 1fr);grid-template-rows: repeat(${lines}, 1fr);`
    
    for (let i = 0; i < imageNum; i++) {
        const each_img_div = document.createElement('div')
        each_img_div.classList.add('each-img')
        
        const ahref = document.createElement('a');
        each_img_div.appendChild(ahref);
        
        const img = document.createElement('img');
        img.style.display = 'none'; 
        ahref.appendChild(img);
        ahref.setAttribute('data-lightbox', "roadtrip");
        ahref.setAttribute('data-title', "中国工艺美术AIGC平台-CARTAI");
        
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
        firstCreate = false
    }
}



function changeCreateImageStatus(isCreating){
    const eachImgElements = img_container.getElementsByClassName('each-img'); 
    createImageing = isCreating;
    
    for (var i = 0; i < eachImgElements.length; i++) {
        let div = eachImgElements[i]; // 获取当前使用的div元素
        let loadingDiv  = div.querySelector('.loading'); // 获取div下的loading标签
        if(createImageing){
            loadingDiv.style.display  = 'block';         
        }else{
            loadingDiv.style.display  = 'none';         
        }
    }   
    
    
}
function wjSetInterfaceVersion(){
    if(baseModelId >= 301 && baseModelId<=304){
        wjIsPro = false
    }else{
        wjIsPro = true
    }
}


async function createImages(prompt, baseModelId,model_id, multiply) {
    
    var model_info = await getModelInfo(model_id);
    var imagePrompt = prompt;
    
    if (model_info.pre_prompt) {
      imagePrompt = model_info.pre_prompt + imagePrompt;
    } 
    if (model_info.tail_prompt) {
      imagePrompt = imagePrompt +","+ model_info.tail_prompt;
    }
    imagePrompt = imagePrompt + ',没有复杂背景，真实自然，细节精准，高清画质，大师作品';
    if(baseModelId!=0){
        //如果不用智能模型，则发给服务器的为用户自主选择的model ID
        model_id = baseModelId
    }
    if(platformId==0){
       sixPenCreateImages(imagePrompt, model_id, multiply,model_info)
                .then(result => {
                    console.log(result['idArray']);
                    if (result['idArray'] != null) {
                        getImgs(result['idArray']);
                    } else {
                        alert(result['error']);
                    }
                })
                .catch(error => console.log('error', error));    
    }else{
/*       wjCreateImages(imagePrompt, model_id, multiply,model_info)//141 19剪纸
                .then(result => {
                    if (result['idArray'] != null) {
                        getImgs(result['idArray']);
                    } else {
                        changeCreateImageStatus(false)
                        alert(result['error']);
                        
                    }
                })
                .catch(error => console.log('error', error)); */         
       hmCreateImages(imagePrompt, model_id, multiply,model_info)//141 19剪纸
                .then(result => {
                    if (result['idArray'] != null) {
                        getImgs(result['idArray']);
                    } else {
                        changeCreateImageStatus(false)
                        alert(result['error']);
                        
                    }
                })
                .catch(error => console.log('error', error)); 
    }

}

function sixPenCreateImages(prompt, model_id, multiply) {
    var myHeaders = new Headers();
    myHeaders.append("ys-api-key", "f6a4e5856501b8202f5727abc75db83f");
    myHeaders.append("User-Agent", "Apifox/1.0.0 (https://apifox.com)");
    myHeaders.append("Content-Type", "application/json");

    var raw = JSON.stringify({
        "prompt": prompt,
        "model_id": "a6ade6392642b36a",
        "multiply": parseInt(multiply),
        "addition": {
            "strength": 0.2
        },
        "width": 512,
        "height": 512
    });

    var requestOptions = {
        method: 'POST',
        headers: myHeaders,
        body: raw,
        redirect: 'follow'
    };
    
    createImageing = true    
    return fetch("https://ston.6pen.art/release/open-custom-model/task", requestOptions)
        .then(response => response.json())
        .then(result => {
            if (result.code === 200 && result.data.ids) {
                return { idArray: result.data.ids, error: null };
            } else {
                return { idArray: null, error: result.code };
            }
        })
        .catch(error => {
            console.log('error', error);
            createImageing = false
            return { idArray: null, error: error };
        });
}


async function sixPenGetImageStatus(id) {
  const url = `https://ston.6pen.art/release/open-custom-model/task?id=`+id;
  
  try {
    const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json', // 假设服务器接受JSON格式数据
            'ys-api-key': 'f6a4e5856501b8202f5727abc75db83f', // 假设服务器接受JSON格式数据
          }
        });
    const data = await response.json();
    
    if (data.code === 200 && data.data.gen_img) {
      return data.data.gen_img;
    } else {
      return null;
    }
  } catch (error) {
    console.error(`Error fetching image data for id: ${id}`, error);
    return null;
  }
}

async function getModelInfo(model_id){
  const url = `https://www.hongmengyishu.com/api/portal/aiimage/getModelInfo?model_id=`+model_id;
  
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
  const url = `https://www.hongmengyishu.com/api/portal/aiimage/getModelPrompt?model_id=`+model_id;
  
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



function getImgs(idArray) {
    
    // 获取每个each-img元素
    const eachImgElements = img_container.getElementsByClassName('each-img');    
   

    var img_list = Array();
    var id_state_list = {};
    for (let i = 0; i < idArray.length; i++) {
            id_state_list[idArray[i]]=0;//0表示还未获得生成image的url
    }   
    let currentIndex = 0; // 当前使用的div元素的索引
    startTime = new Date().getTime();
    creatingTimer = setInterval(async () => {
        const currentTime = new Date().toLocaleTimeString();
        console.log(`Timer executed at: ${currentTime}`);
        const elapsedTime = (new Date().getTime() - startTime)/1000;
        console.log("已执行"+elapsedTime+"秒");
        
        if (idArray.length > 0) {
            for (let i = 0; i < idArray.length; i++) {
                let div = eachImgElements[i]; // 获取当前使用的div元素
                let loadingDiv  = div.querySelector('.loading'); // 获取div下的loading标签
                //计算当前进度
                let completionRate = (elapsedTime / expectedSecondArray[i]) * 100;
                completionRate = Math.min(completionRate, 99);
                completionRate = completionRate.toFixed(2);
                loadingDiv.textContent  = "生成进度："+completionRate+"%";  
                loadingDiv.classList.add('loading');
                const loadingImg = document.createElement('img');
                loadingImg.src = '/themes/Acme/public/assets/img/loading.gif';
                loadingImg.alt = 'Loading...';
                loadingDiv.appendChild(loadingImg);
                loadingImg.style.display = 'block'; // 自定义样式，设置高度为50px  
                
         
                if(!id_state_list[idArray[i]]){
                    var imageUrl = null
                    if(platformId==0){
                        imageUrl = await sixPenGetImageStatus(idArray[i]);
                    }else{
                        imageUrl = await wjGetImageStatus(idArray[i]);
                    }
                    if(imageUrl!=null){
                        console.log(imageUrl);
                        img_list.push(imageUrl);
                        id_state_list[idArray[i]] = 1;//1表示获得url
                        let div = eachImgElements[currentIndex]; // 获取当前使用的div元素
                        let ahref = div.querySelector('a'); // 获取div下的a标签
                        ahref.href = imageUrl;
                        let img = div.querySelector('img'); // 获取div下的img标签
                        img.src = imageUrl; // 将图片URL赋值给img标签的src属性
                        img.setAttribute('data-original', imageUrl);
                        img.style.display  = 'block'; 
/*                        img.addEventListener('click', function() {
                            var currentImgSrc = this.getAttribute('src');
                            layer.open({
                              type: 1,
                              title: false,
                              closeBtn: 1,
                              area: ['auto', 'auto'],
                              skin: 'layui-layer-nobg',
                              shadeClose: true,
                              content: '<img src="' + currentImgSrc + '" style="max-width: 100%; max-height: 100%;">'
                            });
                        }); */                       
                                            
                        let loadingDiv  = div.querySelector('.loading'); // 获取div下的loading标签
                        loadingDiv.style.display  = 'none'; 
                        currentIndex = (currentIndex + 1) % eachImgElements.length; // 更新当前使用的div元素的索引
                    }
                }
    
                // 检查是否所有图片URL都已获取
                if (img_list.length === idArray.length) {
                    clearInterval(creatingTimer); // 所有imageUrl成功获取后停止定时器
                    console.log('All ids have been processed.');
                    clearInterval(creatingTimer); // 所有id处理完毕后停止定时器
                    img_list = Array();
                    changeCreateImageStatus(false)
                    
                    lightbox.option({
                      'resizeDuration': 200,
                      'wrapAround': true
                    })
                    
                }
            }
        
        } else {
            console.log('All ids have been processed.');
            clearInterval(creatingTimer); // 所有id处理完毕后停止定时器
            img_list = Array();
            createImageing = true
            
      
            
            
        }
        
        
        // 检查是否超过30秒
       
        


        if (elapsedTime >= 480000) {
            clearInterval(creatingTimer); // 超过4分钟后停止定时器
            console.log("Timer stopped due to reaching the 480 seconds limit.");
            layer.msg('生成超时，请重新生成', {
            	  icon: 1,
            	  time: 2000 //2秒关闭
            	});
            createImageing = false
        }
    
    }, 5000); // 每隔3秒执行一次
    console.log("getimg timer id:"+creatingTimer)

}


   

function hmCreateImages(prompt, model_id, multiply,model_info) {
    var rawdata = null;
    var auth = getWjAuth();
    if(baseModelId!=0) {
        //如果不用智能模型，且不是mj模型，则所有模型用选定的基模型
        model_id = baseModelId
    }  
    else{
        model_id = model_info.model_id_wj
    }
    wjIsPro = true
    createUrl = "https://www.hongmengyishu.com/api/ai/aiimage/create"
    if(!wjIsPro){
        
        raw = JSON.stringify({
           "model": model_id,
           "prompt": prompt.replace(/{/g, '').replace(/}/g, ''),
           "width": 1024,
           "height": 1024,
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
        image_type = model_info.image_type
        if (image_type) {
            raw.image_type = [image_type];
        }   
    }else{
        //专业版接口
        var rawdata = {
           "model_code": parseInt(model_id),
           "prompt": prompt,
           "auth":auth,
           "isPro":true,
           "width": 1024,
           "height": 1024,
           "prefine_multiple": 1.5,
           "batch_count": multiply,
           "option_param": {
                "model_fusion": null
            },
           "control_net_params": null,
            "advanced_param":{
                "cfg": 7,
                "clipSkip": null,
                "ensd": 0,
                "restore_faces": false,
                "sampler_index": null,
                "sampler_steps": 20,
                "tilling": false,
                "seed": "-1",
                "vae_file": "auto",
                "clip_skip": 2,
            }
        }
        if(hasControlNet){
            controlNetParam = {
              "type": 3,//canny
              "preprocessor": 1,//canny
              "model": 97,//control_v11p_sd15_canny
              "control_weight": 1,
              "starting_control_step": 0,
              "ending_control_step": 1,
              "control_mode": 1,//0-平衡 1-prompt优先 2-control_net优先
              "image_url": controlnetImg,
/*              "image_width": refImgWidth,
              "image_height": refImgHeight,*/
              "processor_res": 512,
              "threshold_a": 171,
              "threshold_b": 255,
              "resize_mode": 1,//参考图缩放模式 0-仅调整大小 1-剪裁后缩放 2-缩放后填充空白
              "pixel_perfect": true
            };
            rawdata.control_net_params = controlNetParam
            hasControlNet = false
        }
        if(model_info.model_fusion) {
            var option_param= {
                "model_fusion": null
            }
            model_info.model_fusion = model_info.model_fusion.replace(/\\/g, "");
            model_fusion = JSON.parse(model_info.model_fusion)
            option_param.model_fusion = model_fusion
            rawdata.option_param = option_param;
        }
        
        image_type = model_info.image_type
        if (image_type) {
            rawdata.image_type = [image_type];
        } 
        raw = JSON.stringify(rawdata); 
        
        
    }

    var requestOptions = {
        method: 'POST',
        body: raw
    };
    
    createImageing = true    
    
    return fetch(createUrl, requestOptions)
        .then(response => response.text())
        .then(result => {
            result = result.replace(/\\\\/g, '');// 去除字符串中的所有反斜杠
            console.log(result)
            const resultObject = JSON.parse(result);
            if (parseInt(resultObject.code) == 200 && resultObject.data.results) {
                const idArrays = resultObject.data.results.map(item => item.key);
                expectedSecondArray = resultObject.data.results.map(item => item.expected_second)
                return { idArray: idArrays, error: null };
            } else {
                var message = resultObject.message.replace('Midjourney','此模型')
                console.log(message)
                return { idArray: null, error: message };
            }
        })
        .catch(error => {
            console.log('error', error);
            createImageing = false
            return { idArray: null, error: error };
        });
        
}   

   

function wjCreateImages(prompt, model_id, multiply,model_info) {
    var auth = getWjAuth()
    var myHeaders = new Headers();
    myHeaders.append("User-Agent", "Apifox/1.0.0 (https://apifox.com)");
    myHeaders.append("Content-Type", "application/json");
    myHeaders.append("Authorization", auth);
    var rawdata = null;
    
    if(baseModelId!=0) {
        //如果不用智能模型，且不是mj模型，则所有模型用选定的基模型
        model_id = baseModelId
    }  
    else{
        model_id = model_info.model_id_wj
    }
 
    if(!wjIsPro){
        createUrl = "https://gate.wujieai.net/wj-open/v1/ai/create"
        raw = JSON.stringify({
           "model": model_id,
           "prompt": prompt.replace(/{/g, '').replace(/}/g, ''),
           "width": 1024,
           "height": 1024,
           "num": multiply,
                
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
        
        
        image_type = model_info.image_type
        if (image_type) {
            raw.image_type = [image_type];
        }   
    }else{
        //专业版接口
        createUrl = "https://gate.wujieai.net/wj-open/v1/ai/pro/create"
        var rawdata = {
           "model_code": parseInt(model_id),
           "prompt": prompt,
           "width": 1024,
           "height": 1024,
           "prefine_multiple": 1.5,
           "batch_count": multiply,
           "option_param": {
                "model_fusion": null
            },
           "control_net_params": null,
            "advanced_param":{
                "cfg": 7,
                "clipSkip": null,
                "ensd": 0,
                "restore_faces": false,
                "sampler_index": null,
                "sampler_steps": 20,
                "tilling": false,
                "seed": "-1",
                "vae_file": "auto",
                "clip_skip": 2,
            }
        }
        if(hasControlNet){
            controlNetParam = {
              "type": 3,//canny
              "preprocessor": 1,//canny
              "model": 97,//control_v11p_sd15_canny
              "control_weight": 1,
              "starting_control_step": 0,
              "ending_control_step": 1,
              "control_mode": 1,//0-平衡 1-prompt优先 2-control_net优先
              "image_url": controlnetImg,
/*              "image_width": refImgWidth,
              "image_height": refImgHeight,*/
              "processor_res": 512,
              "threshold_a": 171,
              "threshold_b": 255,
              "resize_mode": 1,//参考图缩放模式 0-仅调整大小 1-剪裁后缩放 2-缩放后填充空白
              "pixel_perfect": true
            };
            rawdata.control_net_params = controlNetParam
            hasControlNet = false
        }
        if(model_info.model_fusion) {
            var option_param= {
                "model_fusion": null
            }
            model_info.model_fusion = model_info.model_fusion.replace(/\\/g, "");
            model_fusion = JSON.parse(model_info.model_fusion)
            option_param.model_fusion = model_fusion
            rawdata.option_param = option_param;
        }
        
        image_type = model_info.image_type
        if (image_type) {
            rawdata.image_type = [image_type];
        } 
        raw = JSON.stringify(rawdata); 
        
        
    }

    var requestOptions = {
        method: 'POST',
        headers: myHeaders,
        body: raw,
        redirect: 'follow'
    };
    
    createImageing = true    
    
    return fetch(createUrl, requestOptions)
        .then(response => response.text())
        .then(result => {
            console.log(result)
            const resultObject = JSON.parse(result);
            if (parseInt(resultObject.code) == 200 && resultObject.data.results) {
                const idArrays = resultObject.data.results.map(item => item.key);
                expectedSecondArray = resultObject.data.results.map(item => item.expected_second)
                return { idArray: idArrays, error: null };
            } else {
                var message = resultObject.message.replace('Midjourney','此模型')
                console.log(message)
                return { idArray: null, error: message };
            }
        })
        .catch(error => {
            console.log('error', error);
            createImageing = false
            return { idArray: null, error: error };
        });
        
}   



function getWjAuth(){
    var publicKey = "-----BEGIN RSA PUBLIC KEY---\n" +
        "MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAJ4KjOPeKC834HXkxPgxKwbpa6SNOBm3+KGaNW0T0mQ28Vz9NNrXA7b74scGhsjIH8FWYwNB0en+XjQZJ+VSnIUCAwEAAQ==\n" +
        "-----END RSA PUBLIC KEY-----";
 //签名流程
    let timestamp = parseInt(Date.now()/1000).toString();
    let appId = 'wjok9a9wy8812a9h3b';
    let plaintText = JSON.stringify({
      appId: appId,//用于指定客户appID
      timestamp: timestamp
    })
    
    var auth = JSON.stringify({
        appId:appId,
        secretKeyVersion: "1",
        sign: sign(plaintText),
        original:plaintText
    })
    return auth;
    

   
}    

function sign(plaintText) {
    let key = "-----BEGIN PRIVATE KEY-----\n" +
        "MIIBVQIBADANBgkqhkiG9w0BAQEFAASCAT8wggE7AgEAAkEA2lguoGoPbEsJ2RCfyMMUmgJ+P60DIp6KfGYREAQt8ykaWRWB/qPnzNVZXq6+drbDSWtxoIKCCGxWFIOz9FZ3AQIDAQABAkAFaGiXuybAg7OFzXtKpHfHuHr2Ymds6aS0D+5ZYWQetQy69blSUae3ENI/vJz9CuR2eHKGah+g1avfyJIbZQdRAiEA/eJgHTeasLOCPfSRmRm+J/vdXa7cTeR6z+mxX4Ny69ECIQDcKfzc5liR27i5Xbw8AAv+rL1sKFpJelViLRPlq/sUMQIgMnkfzI8yZLC7Ut/dVkek40QSRT6vM5kZxV3bUnbKkRECIQCxlXSOwYXjcLVZX1A7nVYPgEnx+cKJzhEc9Xs9IDGPoQIhALXcYRcaydB9yLSqf4LqIXXo8Br9r6lZ0XeoPyxoRkuA" +
        "-----END PRIVATE KEY-----\n";
    var prk = forge.pki.privateKeyFromPem(key);
    const md = forge.md.sha256.create();
    md.update(plaintText, "utf8");
    var sign64 = prk.sign(md);
    var sign = forge.util.encode64(sign64)
    return sign;
}

function wjGetHeader(){

    var auth = getWjAuth()
    var myHeaders = new Headers();
    myHeaders.append("User-Agent", "Apifox/1.0.0 (https://apifox.com)");
    myHeaders.append("Content-Type", "application/json");
    myHeaders.append("Authorization", auth);
    return myHeaders;
}

function wjGetModels() {
    var myHeaders = wjGetHeader();
    
    var requestOptions = {
       method: 'GET',
       headers: myHeaders,
       redirect: 'follow'
    };
    
    fetch("https://gate.wujieai.net/wj-open/v1/ai/pro/model_base_infos", requestOptions)
       .then(response => response.text())
       .then(result => console.log(result))
       .catch(error => console.log('error', error));   
   
} 
async function wjGetImageStatus(key) {
    var myHeaders = wjGetHeader();
    
    try {
        if(wjIsPro){
            queryUrl = "https://gate.wujieai.net/wj-open/v1/ai/pro/info?key="+key
            var requestOptions = {
               method: 'GET',
               headers: myHeaders,
               redirect: 'follow'
            };
            const response = await fetch(queryUrl, requestOptions)
            const responseObject = await response.json();
            
            if (parseInt(responseObject.code) == 200 && responseObject.data && responseObject.data.picture_url.length>0) {
                return responseObject.data.picture_url
            } else {
                return null;
            }
        }else{
            queryUrl = "https://gate.wujieai.net/wj-open/v1/ai/generating_info"
            var raw = JSON.stringify([
               key
            ]);
            var requestOptions = {
               method: 'POST',
               headers: myHeaders,
               body: raw,
               redirect: 'follow'
            };
            const response = await fetch(queryUrl, requestOptions)
            const responseObject = await response.json();
            
            if (parseInt(responseObject.code) == 200 && responseObject.data && responseObject.data.list.length>0) {
                imgData = responseObject.data.list[0]
                console.log(imgData)
                if(imgData.picture_url.length>0){
                    return imgData.picture_url
                }else{
                    return null
                }
            } else {
                return null;
            }            
        }   
    
    } catch (error) {
        console.error(`Error fetching image data for id: ${key}`, error);
        return null;
    }  
   
} 

function wjQueryImage(key) {
    var myHeaders = wjGetHeader();
    
    var raw = JSON.stringify(key);
    
    var requestOptions = {
       method: 'POST',
       headers: myHeaders,
       body: raw,
       redirect: 'follow'
    };
    
    fetch("https://gate.wujieai.net/wj-open/v1/ai/pro/generating_info", requestOptions)
       .then(response => response.text())
       .then(result => console.log(result))
       .catch(error => console.log('error', error));
   
} 
function wjGetResource(modelId) {

    var requestOptions = {
       method: 'GET',
       headers: wjGetHeader(),
       redirect: 'follow'
    };
    
    fetch("https://gate.wujieai.net/wj-open/v1/ai/default_resource?model="+modelId, requestOptions)
       .then(response => response.text())
       .then(result => console.log(result))
       .catch(error => console.log('error', error));
   
} 

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
   
} 

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
 
} 
function generateRandomString(length) {
  var result = '';
  var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var charactersLength = characters.length;

  for (var i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }

  return result;
}


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
 
} 

 function analysisPrompt() {
  
      var image_url = $(".graph-d20-search-wrapper-input").val();
      image_url = "https://www.hongmengyishu.com/upload/"+image_url
      console.log(image_url)
      wjAnalysisPrompt(image_url)
}   

function autoDesign(){
      var image_url = $(".graph-d20-search-wrapper-input").val();
      image_url = "https://www.hongmengyishu.com/upload/"+image_url
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
        
            content: ['https://www.hongmengyishu.com/portal/searchimage/aiimage?image_url='+image_url, 'no'],
        
        });
        console.log(index)   

}
function doodleDesign(){
      var image_url = $(".graph-d20-search-wrapper-input").val();
      image_url = "https://www.hongmengyishu.com/upload/"+image_url
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
        
            content: ['https://www.hongmengyishu.com/portal/searchimage/doodledesign?image_url='+image_url, 'no'],
        
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
        apiURL = 'https://www.hongmengyishu.com/api/portal/aiimage/getPoemSentence?category='+category; 
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
    
  const url = `https://www.hongmengyishu.com/api/portal/aiimage/getBookDesign?category=`+modelName+"&book="+book;
  
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
    designPrompts = await getPoemDesign(modelName,poem,poemMode);
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

async function getPoemDesign(modelName,poem,poemMode) {
    
  const url = `https://www.hongmengyishu.com/api/portal/aiimage/getPoemDesign?category=`+modelName+"&poem="+poem+'&poemmode='+poemMode;
  
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

   
   