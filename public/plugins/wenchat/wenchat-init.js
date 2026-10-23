// 全局初始化函数
function initWenChat(options) {
  // 默认配置
  var defaults = {
    container: '#wenchat-container',
    apiKey: '',
    // 其他默认配置...
  };
  
  // 合并配置
  options = Object.assign({}, defaults, options);
  
  // 初始化核心
  WenChat.Core.init({
    apiKey: options.apiKey
  });
  
  // 初始化UI
  WenChat.UI.init(options.container);
  
  // 全局暴露关键词跳转函数
  window.jumptokey = function(key) {
    event.preventDefault();
    layer.open({
      type: 1,
      title: `您点击了关键词: ${key}`,
      content: `
        <div style="padding: 20px;">
          <button class="wenchat-keyword-btn" 
            onclick="window.open('/portal/Search/index?keyword=${key}', '_blank')">
            搜索作品
          </button>
          <button class="wenchat-keyword-btn" 
            onclick="window.open('/portal/visual?tag=${key}', '_blank')">
            查看图谱
          </button>
        </div>
      `,
      area: ['', 'auto'],
      btn: [],
      shadeClose: true
    });
  };
  
  // 如果有文章ID，添加解读按钮
  var urlParams = new URLSearchParams(window.location.search);
  var articleId = urlParams.get('id');
  
  if (articleId) {
    var header = document.querySelector('.wenchat-header');
    if (header) {
      var interpretBtn = document.createElement('button');
      interpretBtn.className = 'wenchat-keyword-btn';
      interpretBtn.textContent = '解读当前作品';
      interpretBtn.onclick = function() {
        fetchArticleContent(articleId);
      };
      header.appendChild(interpretBtn);
    }
  }
  
  // 获取文章内容
  function fetchArticleContent(id) {
    fetch(`https://www.hongmengyishu.com/portal/chat/getContent?id=${id}`)
      .then(function(res) {
        return res.json();
      })
      .then(function(data) {
        if (data.status === "success") {
          WenChat.UI.sendMessage(data.data);
        }
      })
      .catch(console.error);
  }
}