// 确保核心已加载
if (!window.WenChat) window.WenChat = {};
if (!WenChat.Core) throw new Error('WenChat.Core must be loaded first');

WenChat.UI = (function() {
  var core = WenChat.Core;
  var dom = {};
  var uiState = {
    currentStream: null
  };
  
  // 初始化DOM元素
  function init(container) {
    // 创建聊天容器
    dom.container = typeof container === 'string' ? 
      document.querySelector(container) : container;
    
    dom.container.innerHTML = `
      <div class="wenchat-header">
        <span>问艺-文博工艺美术大模型</span>
      </div>
      <div class="wenchat-messages"></div>
      <div class="wenchat-input">
        <input type="text" class="wenchat-input-field" placeholder="输入消息...">
        <button class="wenchat-send-btn">发送</button>
      </div>
      <div class="wenchat-presets"></div>
      <div class="wenchat-typing">通义千问正在输入...</div>
    `;
    
    // 缓存DOM元素
    dom.messages = dom.container.querySelector('.wenchat-messages');
    dom.input = dom.container.querySelector('.wenchat-input-field');
    dom.sendBtn = dom.container.querySelector('.wenchat-send-btn');
    dom.typing = dom.container.querySelector('.wenchat-typing');
    dom.presets = dom.container.querySelector('.wenchat-presets');
    
    // 绑定事件
    dom.sendBtn.addEventListener('click', sendMessage);
    dom.input.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') sendMessage();
    });
    
    // 初始欢迎消息
    setTimeout(function() {
      addMessage('你好！我是问艺，我是一个专业的文博和工艺美术领域的大模型。我可以为你解答专业的文博和工艺美术领域问题，还可以根据你的问题搜索相关作品和查看图谱。', false);
    }, 500);
  }
  
  // 添加消息
  function addMessage(text, isUser) {
    var messageDiv = document.createElement('div');
    messageDiv.className = 'wenchat-message ' + 
      (isUser ? 'wenchat-user-message' : 'wenchat-bot-message');
    

    messageDiv.innerHTML = text;
    dom.messages.appendChild(messageDiv);
    scrollToBottom();
    

    
  }
  
  // 发送消息
  function sendMessage(content) {
    var message = content || dom.input.value.trim();
    if (!message) return;
    
    // 清空输入框
    if (!content) dom.input.value = '';
    
    // 添加用户消息
    addMessage(message, true);
    
    // 显示正在输入
    showTyping();
    
    // 禁用输入
    setInputDisabled(true);
    
    // 调用API
    var response = core.callAPI(message, { stream: true });
    
    // 创建消息容器
    var botMessageDiv = document.createElement('div');
    botMessageDiv.className = 'wenchat-message wenchat-bot-message';
    dom.messages.appendChild(botMessageDiv);
    
    // 添加取消按钮
    var cancelBtn = document.createElement('button');
    cancelBtn.className = 'wenchat-cancel-btn';
    cancelBtn.innerHTML = '×';
    botMessageDiv.appendChild(cancelBtn);
    
    // 处理流式响应
    response.then(function(res) {
      return res.body.getReader();
    }).then(function(reader) {
      var fullContent = '';
      
      uiState.currentStream = core.handleStreamResponse(reader, function(update) {
        if (update.isStreaming) {
          fullContent = update.fullContent;
          botMessageDiv.innerHTML = window.marked.parse(fullContent) + 
            '<button class="wenchat-cancel-btn">×</button>';
          scrollToBottom();
        } else if (update.complete) {
          hideTyping();
          setInputDisabled(false);
          cancelBtn.remove();
          generatePresetQuestions();
        }
      });
      
      cancelBtn.onclick = function() {
        uiState.currentStream.cancel();
        botMessageDiv.innerHTML += ' (已中断)';
        hideTyping();
        setInputDisabled(false);
      };
    }).catch(function(error) {
      hideTyping();
      setInputDisabled(false);
      addMessage('出错: ' + error.message, false);
    });
  }
  
  // 生成预设问题
  function generatePresetQuestions() {
    var prompt = `给我推荐3个相关问题,以json格式返回。json示例如下：
            [
                "如何从纹饰特征鉴别明代青花瓷与清代青花瓷？",
                "商周青铜器上的铭文通常包含哪些重要历史信息？",
                "故宫博物院收藏的《清明上河图》在艺术表现上有哪些独特之处？"
            ]
            直接给出问题json，不要有任何其他相关描述`;
    
    core.callAPI(prompt, { 
      stream: false,
      saveHistory: false 
    }).then(function(res) {
      return res.json();
    }).then(function(data) {
      try {
        var questions = JSON.parse(data.choices[0].message.content
          .replace(/```json|```/g, ''));
        
        if (Array.isArray(questions)) {
          dom.presets.innerHTML = '';
          questions.forEach(function(q) {
            var btn = document.createElement('button');
            btn.className = 'wenchat-preset-btn';
            btn.textContent = q.replace(/<[^>]+>/g, '');
            btn.onclick = function() { sendMessage(q); };
            dom.presets.appendChild(btn);
          });
        }
      } catch (e) {
        console.error('Preset questions error:', e);
      }
    });
  }
  
  // 工具函数
  function showTyping() {
    dom.typing.style.display = 'block';
    scrollToBottom();
  }
  
  function hideTyping() {
    dom.typing.style.display = 'none';
  }
  
  function setInputDisabled(disabled) {
    dom.input.disabled = disabled;
    dom.sendBtn.disabled = disabled;
    if (!disabled) dom.input.focus();
  }
  
  function scrollToBottom() {
    dom.messages.scrollTop = dom.messages.scrollHeight;
  }
  
  // 公开方法
  return {
    init: init,
    addMessage: addMessage,
    sendMessage: sendMessage
  };
})();