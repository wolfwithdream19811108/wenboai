let chatElements = {}; 
let interpretBtn = null;
let reader;
let responseContainer;
let botMessageDiv;
let presetButtonsContainer = document.querySelector('.preset-buttons');
let isLogin = false;



// 控制携带的对话轮数
const MAX_TURNS = 3;

// 对话历史
let conversationHistory = [];    



    
const API = {
  newConv:   '/portal/chat/newConversation',
  getConvs:  '/portal/chat/getConversations',
  getMsgs:   '/portal/chat/getMessages'  // ?conversation_id=ID
};



// 当前对话流 ID
let conversationId = null;
// 消息序号计数器
let messageSequence = 0;
// 使用同源相对地址，自动继承当前协议、主机和端口。
// 这也避免 localhost 被错误拼成 www.localhost。
const GET_CONTENT_API = '/portal/chat/getContent';
const HONGMENG_API_URL = '/portal/chat/jianyuanai';

/**
 * 异步判断用户是否已登录
 * @returns {Promise<boolean>} 已登录返回 true，否则返回 false
 */
async function isUserLoggedIn() {
  try {
    const response = await fetch('/api/user/public/isLogin', {
      method: 'GET',
      credentials: 'same-origin'
    });
    if (!response.ok) {
      console.error('登录状态检查失败，HTTP 状态：', response.status);
      return false;
    }
    const res = await response.json();
    // ThinkCMF 返回的 code === 1 表示已登录
    return res.code === 1;
  } catch (e) {
    console.error('异步请求异常：', e);
    return false;
  }
}








// 定义全局函数 window.initializeChat
window.initializeChat = async function(elements) {
    // 检查传入的参数是否有效
    if (!elements || !elements.chatMessages || !elements.messageInput || !elements.sendButton || !elements.typingIndicator) {
        console.error("初始化失败：缺少必要的控件元素。");
        return;
    }

    // 将传入的控件元素保存在一个全局对象中，方便后续操作
    chatElements.chatMessages = elements.chatMessages;
    chatElements.messageInput = elements.messageInput;
    chatElements.sendButton = elements.sendButton;
    chatElements.analysisImageButton = elements.analysisImageButton;
    chatElements.typingIndicator = elements.typingIndicator;
    presetButtonsContainer = elements.presetButtons;

    // 绑定发送按钮点击事件
    chatElements.sendButton.addEventListener('click', sendMessage);

    // 绑定输入框回车事件
    chatElements.messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    // 动态插入侧边栏并加载历史会话
    createSidebar();
    isLogin =  await isUserLoggedIn();
    console.log(isLogin)
    
    
    
    
    // 初始欢迎消息
    setTimeout(() => {
        addMessage('你好！我是鉴渊，我是一个专业的文博和工艺美术领域的大模型。我可以为你做以下事情：<p> 1、解答专业的文博和工艺美术领域问题<p> 2、上传文博或者工艺美术图片，分析器型、材质、纹饰等信息<p> 3、根据你的问题搜索相关作品和查看图谱', false);
    }, 500);

    // 获取 URL 参数，判断是否有文章 ID
    const urlParams = new URLSearchParams(window.location.search);
    const articleId = urlParams.get('id');
    if (articleId) {
        // 创建解读按钮
        const header = document.querySelector('.chat-header');
        interpretBtn = document.createElement('button');
        interpretBtn.id = 'interpret-btn';
        interpretBtn.textContent = '解读当前作品';

        // 添加点击事件
        interpretBtn.onclick = () => {
            interpretBtn.disabled = true;
            analysisArticleContent(articleId);
        };

        // 插入到标题右侧
        header.appendChild(interpretBtn);

        // 添加加载动画样式
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .loading {
                display: inline-block;
                width: 12px;
                height: 12px;
                border: 2px solid white;
                border-top-color: transparent;
                border-radius: 50%;
                animation: rotate 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
    }
};

function createSidebar() {
  // 1) 确保根容器用 flex 布局
  const root = window.chatRoot;
  root.style.display = 'flex';
  root.style.alignItems = 'stretch';

  // 2) 构建侧边栏
  const sidebar = document.createElement('div');
  sidebar.id = 'chat-sidebar';
  Object.assign(sidebar.style, {
    width: '200px',
    borderRight: '1px solid #ddd',
    background: '#fafafa',
    padding: '10px',
    boxSizing: 'border-box',
    marginTop:'10px',
    height:'95%',
    overflowY: 'auto'
  });

  const btn = document.createElement('button');
  btn.id = 'new-conversation-btn';
  btn.innerHTML = '<i class="fa fa-pencil-square-o fa-2x"></i>';
  btn.style.background = 'none';
  btn.style.border = 'none';
  btn.style.boxShadow = 'none';
  btn.style.padding = '0';
  btn.style.margin = '0';
  btn.style.outline = 'none';
  btn.setAttribute('title', '新建对话');
  btn.setAttribute('aria-label', '新建对话');
  
  

  btn.addEventListener('click', async () => {

      if (isLogin) {
        // 已登录，正常创建对话
        await initConversation();
        await loadConversations();
        openConversation(conversationId);
      } else {
        // 未登录，提示并跳转
        layer.msg("请先登录后再使用")
      }
  });

  const ul = document.createElement('ul');
  ul.id = 'conversation-list';
  Object.assign(ul.style, { listStyle: 'none', padding: '0', margin: '0' });

  sidebar.appendChild(btn);
  sidebar.appendChild(ul);

  // 3) 插到 .chat-container 之前
  const chatPanel = root.querySelector('.chat-container');
  root.insertBefore(sidebar, chatPanel);

  // 4) 让聊天面板自适应剩余宽度
  chatPanel.style.flex = '1';

  // 5) 首次加载历史对话
  
  loadConversations();
}

/**
 * 更新侧边栏中指定会话项的标题
 * @param {number|string} convId 会话 ID
 * @param {string} title 新的标题
 */
function updateConversationTitle(convId, title) {
  const li = document.querySelector(
    `#conversation-list li[data-id="${convId}"]`
  );
  if (li) {
    li.textContent = title;
  }
}
    
    /**
     * 从后端拉历史会话，按 start_time 降序渲染到 #conversation-list
     */
    async function loadConversations() {
      try {
        const res = await fetch(API.getConvs);
        const list = await res.json();
    
    
        const ul = document.getElementById('conversation-list');
        ul.innerHTML = '';
    
        list.forEach(conv => {
          const li = document.createElement('li');
          li.textContent = conv.title || '（未命名会话）';
          li.dataset.id = conv.id;
          Object.assign(li.style, {
            padding: '6px',
            cursor: 'pointer',
            borderBottom: '1px solid #eee'
          });
          li.addEventListener('click', () => openConversation(conv.id));
          ul.appendChild(li);
        });
      } catch (e) {
        console.error('加载会话列表失败', e);
      }
    }
    
    /**
     * 打开某个会话流：  
     *  - 设置全局 conversationId  
     *  - 清空并重建消息区  
     *  - 拉取该对话流下所有历史消息并渲染  
     */
    async function openConversation(convId) {
      conversationId = convId;
      messageSequence = 0;
      // Clear the conversation history
      conversationHistory = [];    
      // 清空聊天区
      chatElements.chatMessages.innerHTML = '';
      chatElements.chatMessages.scrollTop = 0;
    
      try {
        const res  = await fetch(`${API.getMsgs}?conversation_id=${convId}`);
        const msgs = await res.json();
    
        msgs.forEach(m => {
          // isUser = role==='user'
          const parsedMsg = marked.parse(m.content)
          addMessage(parsedMsg, m.role === 'user');
          messageSequence++;
        });
      } catch (e) {
        console.error('加载消息失败', e);
      }
    }
    

    
     function jumptokey(key) {
        // 阻止默认链接跳转行为
        event.preventDefault();
            
        layer.open({
            type: 1,  // 类型1为自定义弹出框
            title: `您点击了关键词: ${key}`,
            content: `
                <div style="padding: 20px;">
                    <button class="btn btn-primary btn-deep-analysis" onclick="window.open('/portal/Search/index?keyword=${key}', '_blank')">搜索作品</button>
                    <button class="btn btn-primary btn-deep-analysis" onclick="window.open('/portal/visual?tag=${key}', '_blank')">查看图谱</button>
                </div>
            `,
            area: ['', 'auto'], // 弹出框的宽度和高度
            btn: [], // 不需要弹出框的按钮
            shadeClose: true, // 点击遮罩关闭
        });
    }
        
    
    
    function presetQuestion(question) {
/*        document.getElementById('message-input').value = question;
        sendMessage();*/
        (async () => {
            if(!isLogin){
                layer.msg("请先登录后再使用")
                return;
            }
            
            if (!conversationId) {
                await initConversation();
                await loadConversations();
            }
            
            document.getElementById('message-input').value = question;
            sendMessage();
        })();
    }



/**
 * 新建一个会话流，从后端拿到 conversationId
 */
async function initConversation() {
  try {
    const res = await fetch('/portal/chat/newConversation', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    conversationId = data.conversation_id;
    console.log('新会话 ID:', conversationId);
  } catch (e) {
    console.error('创建会话失败', e);
  }
}




/**
 * 保存一条消息到后端
 * @param {'user'|'system'} role 
 * @param {string} content 
 */
async function saveMessage(role, message) {
  if (!conversationId) return;
  messageSequence++;
  try {
    await fetch('/portal/chat/saveMessage', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        conversation_id: conversationId,
        role: role,
        message: message,
        timestamp: Date.now(),
        sequence: messageSequence
      })
    });
  } catch (e) {
    console.error('保存消息失败', e);
  }
}









 async function handleStreamResponse(reader, isCancelled) {
    let chunkCount = 0;
    const decoder = new TextDecoder();
    
    // 创建机器人消息区域
    botMessageDiv = document.createElement('div');
    botMessageDiv.classList.add('message', 'bot-message', 'message-loading');
    
    // 创建思考内容的容器并立即添加
    const reasoningContainer = document.createElement('div');
    reasoningContainer.classList.add('reasoning-container');
    reasoningContainer.style.marginBottom = '10px';
    
    // 创建标题
    const reasoningHeader = document.createElement('div');
    reasoningHeader.classList.add('reasoning-header');
    reasoningHeader.innerHTML = '思考过程:';
    reasoningHeader.style.color = '#666';
    reasoningHeader.style.fontSize = '0.9em';
    reasoningHeader.style.fontWeight = 'bold';
    
    // 创建内容区域
    const reasoningBody = document.createElement('div');
    reasoningBody.classList.add('reasoning-body');
    reasoningBody.style.padding = '10px';
    reasoningBody.style.background = '#f5f5f5';
    reasoningBody.style.borderRadius = '5px';
    reasoningBody.style.marginTop = '5px';
    reasoningBody.style.whiteSpace = 'pre-wrap';
    reasoningBody.style.fontSize = '0.9em';
    reasoningBody.style.maxHeight = '200px';
    reasoningBody.style.overflowY = 'auto';
    
    // 组装思考内容区域
    reasoningContainer.appendChild(reasoningHeader);
    reasoningContainer.appendChild(reasoningBody);
    
    // 创建实际回答内容的容器
    responseContainer = document.createElement('div');
    responseContainer.classList.add('response-container');
    
    // 将两个容器添加到消息div
    botMessageDiv.appendChild(reasoningContainer);
    botMessageDiv.appendChild(responseContainer);
    
    // 添加到聊天消息区域
    chatElements.chatMessages.appendChild(botMessageDiv);
    

    
    let fullResponse = '';
    let reasoningContent = '';


    // 隐藏思考容器，仅当有数据时显示
    reasoningContainer.style.display = 'none';

    try {
        while (!isCancelled) {
            const { done, value } = await reader.read();
            if (done) break;

            const chunk = decoder.decode(value);
            const lines = chunk.split('\n').filter(line => line.trim() !== '');
            
            
            for (const line of lines) {
                if (line.startsWith('data:')) {
                    try {
                        // 解析JSON数据
                        const data = JSON.parse(line.substring(5).trim());
                       // console.log(data)
                        //const data = JSON.parse(line);

                        if (data.choices && data.choices[0]) {
                            // 处理思考内容
                            if (data.choices[0].delta?.reasoning_content) {
                                // 显示思考容器
                                reasoningContainer.style.display = 'block';
                                
                                // 更新思考内容
                                reasoningContent += data.choices[0].delta.reasoning_content;
                                reasoningBody.textContent = reasoningContent;
                                
                                // 自动滚动到底部
                                reasoningBody.scrollTop = reasoningBody.scrollHeight;
                                
                                // 记录到控制台
                                console.log('[Thinking]', data.choices[0].delta.reasoning_content);
                            }

                            // 处理正常回答内容
                            const content = data.choices[0].delta?.content;
                            if (content) {
                                fullResponse += content;
                                responseContainer.innerHTML = marked.parse(fullResponse);
                                chatElements.chatMessages.scrollTop = chatElements.chatMessages.scrollHeight;
                            }

                            // 检查是否为流的结束
                            if (data.choices[0].finish_reason === 'stop') {
                                conversationHistory.push({ 
                                    role: 'assistant', 
                                    content: fullResponse,
                                    reasoning: reasoningContent
                                });
                                botMessageDiv.classList.remove('message-loading');
                                // —— 流结束，保存系统回复 —— 
                                saveMessage('system', fullResponse);
                                // 触发3个相关问题
                                give3question(fullResponse);
                                
                                
                                fullResponse += "\n\n--内容由AI生成，仅供参考";
                                responseContainer.innerHTML = marked.parse(fullResponse);
                                chatElements.chatMessages.scrollTop = chatElements.chatMessages.scrollHeight;                                 
                                
                                
                                
                                break;
                            }
                        }
                    } catch (e) {
                        console.error('解析JSON失败:', e);
                        console.log('Non-JSON response line:', line);
                        console.log('忽略此错误并继续处理流...');
                        continue;
                    }
                }
            }
        }
    } catch (error) {
        responseContainer.textContent += ` (错误: ${error.message})`;
    } finally {
        // 如果没有显示任何思考内容，隐藏思考区域
        if (reasoningContent.trim() === '') {
            reasoningContainer.style.display = 'none';
        }
        botMessageDiv.classList.remove('message-loading');
        
        // 还原按钮状态
        handleSendButtonClick(false)
    }
}

    function addMessage(text, isUser) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        messageDiv.classList.add(isUser ? 'user-message' : 'bot-message');
        
        // 安全地解析和插入HTML内容
        if (typeof text === 'string' && text.includes('<')) {
            // 创建一个临时div来解析HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = text;
            
            // 安全地克隆节点到消息div中
            while (tempDiv.firstChild) {
                messageDiv.appendChild(tempDiv.firstChild);
            }
        } else {
            // 普通文本内容
            messageDiv.textContent = text;
        }
        
        chatElements.chatMessages.appendChild(messageDiv);
        chatElements.chatMessages.scrollTop = chatElements.chatMessages.scrollHeight;
        
        // 返回创建的消息元素，以便后续操作
        return messageDiv;
    }
  
    
async function sendMessage(event = null, message = null, isAddMessage = true) {
    
    if(!isLogin){
        layer.msg("请先登录后再使用")
        return;
    }
     // 检查并初始化会话
    if (!conversationId) {
        await initConversation();
        await loadConversations();
    }
   
    
    
    if (!message) {
        message = chatElements.messageInput.value.trim();
    }

    if (message === '') return;

    // Disable input and button, and show "停止" (Stop)
    handleSendButtonClick(true);  // Start sending, changes button to "停止"

    // Declare reader variable
    reader;
    let isCancelled = false;

    // Add user message
    if (isAddMessage) {
        const isFirstMsg = (messageSequence === 0);//saveMessage中messageSequence会++，不能放到后面
        addMessage(message, true);
        // 保存到后端
        saveMessage('user', message);
        
        
        // 如果是第一条，取前20字并更新侧边栏
        if (isFirstMsg) {
          const title = message.length > 20 
            ? message.substring(0, 20) 
            : message;
          updateConversationTitle(conversationId, title);
        }        
    }
    chatElements.messageInput.value = '';

    // Show typing indicator
    chatElements.typingIndicator.style.display = 'block';
    chatElements.chatMessages.scrollTop = chatElements.chatMessages.scrollHeight;

    try {
        // Get stream data and pass it to handleStreamResponse
        reader = await callAI(message);
        chatElements.typingIndicator.style.display = 'none';
        await handleStreamResponse(reader, isCancelled);  // Handle the stream response
    } catch (error) {
        chatElements.typingIndicator.style.display = 'none';
        addMessage('抱歉，获取回复时出错: ' + error.message, false);
    } finally {
        // Reset state and enable input after the request completes or is cancelled
        if (!isCancelled) {
            handleSendButtonClick(false);  // Restore the button text to "发送" (Send)
            
        }
    }
}



    // 处理非流式响应
    async function handleResponse(data) {
        // 获取API响应中的内容并显示
        console.log(data);
        
        if (data && data.choices && data.choices[0] && data.choices[0].message) {
            let content = data.choices[0].message.content;
            content = content.replace(/```json|```/g, '').trim();
            
            try {
                const parsedData = JSON.parse(content);
                console.log(parsedData);
                
                if (Array.isArray(parsedData)) {
 
                    
                    // 清空现有按钮
                    presetButtonsContainer.innerHTML = '';
                    
                    // 为每个问题创建按钮
                    parsedData.forEach((question, index) => {
                        question=question.replace(/<a\b[^>]*>(.*?)<\/a>/gi, '$1');
                        const button = document.createElement('button');
                        button.textContent = question;
                        button.addEventListener('mouseover', () => {
                            chatElements.messageInput.value = button.textContent; 
                        });                        
                        button.onclick = function() {
                            presetQuestion(question);
                        };
                        presetButtonsContainer.appendChild(button);
                    });
                    
                    parsedData.forEach((question, index) => {
                        console.log(`问题 ${index + 1}:`, question);
                    });
                } else {
                    console.error("API返回的数据格式不符合预期:", content);
                }
            } catch (error) {
                console.error("JSON解析错误:", error);
                console.error("原始内容:", content);
            }
        }
    }  
    // 处理发送消息
    async function give3question(content) {

        try {
            message = content+`\n给我推荐和以上内容密切相关的3个相关问题,以json格式返回。json示例如下：
            [
                "如何从纹饰特征鉴别明代青花瓷与清代青花瓷？",
                "商周青铜器上的铭文通常包含哪些重要历史信息？",
                "故宫博物院收藏的《清明上河图》在艺术表现上有哪些独特之处？"
            ]
            直接给出问题json，不要有任何其他相关描述`
            const data = await callAI(message,'json_object',false,false);
            await handleResponse(data); // 使用API响应数据
        } catch (error) {
            chatElements.typingIndicator.style.display = 'none';
            addMessage('抱歉，获取回复时出错: ' + error.message, false);
        } finally {
            // 重新启用输入
            chatElements.messageInput.disabled = false;
            chatElements.messageInput.focus();
        }
    }        
    async function analysisArticleContent(articleId) {
        try {
            const response = await fetch(`${GET_CONTENT_API}?id=${articleId}`);
    
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
    
            const data = await response.json();
    
            if (data.status !== "success" || data.code !== 200) {
                throw new Error(`API error: ${data.message || 'Unknown error'}`);
            }
    
            // Start sending and handle button
            handleSendButtonClick(true);
    
            sendMessage(null, data.data, false);  // Send the content to the chat
    
        } catch (error) {
            console.error('Error fetching article:', error);
            throw error;
        }
    }

  
       
    async function callAI(message, response_format = 'json_object', isStream = true, isSaveHistory = true) {
        const messagesToSend = [
            ...conversationHistory.slice(-MAX_TURNS),
            { role: "user", content: message } // 仅发送用户的输入消息
        ];
    
        if (isSaveHistory) {
            conversationHistory.push({ role: "user", content: message });
        }
    
        let apiUrl = HONGMENG_API_URL;
        let requestData = {
            messages: messagesToSend,
            response_format,
            'data_id':"jianyuan",
            'model_type':"jianzhu",
            isStream
        };
        
        const headers = { 'Content-Type': 'application/json' };
    
        try {
            console.log("isStream:", isStream);
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers,
                body: JSON.stringify(requestData)
            });
            if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || `API请求失败: ${response.status}`);
            }
    
            return isStream ? response.body.getReader() : response.json();
        } catch (error) {
            console.error('API调用失败:', error);
            throw error;
        }
    }

    
    async function analysisObject() {
        console.log('开始分析图片...');
    
        var image_url = document.getElementById('ref_image_url').src;
    
        if (!image_url) {
            console.error('未提供图片URL');
            alert('请先选择或上传图片');
            return;
        }
    

        console.log('图片URL:', image_url);
        // Set the image src
        $("#ref_image_url").attr("src", image_url);
    
        // Show image in chat box
        const imageMessage = `
            <div class="image-message">
                <img src="${image_url}" alt="分析图片" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
            </div>
        `;
        addMessage(imageMessage, false);
    
        // Show "正在分析" (Analyzing) message
        const analyzingMessage = addMessage('正在分析图片...', false);
    
        try {
            handleSendButtonClick(true);
    
            // Call API and handle stream response
            const response = await fetch('/portal/chat/jianzhuAnalysis', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    image: image_url,
                    stream: true
                })
            });
    
            if (!response.ok) {
                throw new Error(`API请求失败: ${response.status}`);
            }
    
            reader = response.body.getReader();
            await handleStreamResponse(reader);
    
        } catch (error) {
            console.error('图片分析错误:', error);
            if (analyzingMessage && analyzingMessage.parentNode) {
                analyzingMessage.remove();
            }
            addMessage(`图片分析失败: ${error.message}`, false);
        } finally {
            // Reset the state after analysis completes or fails
            handleSendButtonClick(false)
            enablePresetButtons();
        }
    }


        
    function handleSendButtonClick(isSending = true) {
        // Flag to track if the "已中断" message has been added
        let interruptedMessageAdded = false;
    
        // If sending, change the button text to '停止' (Stop) and disable the button
        if (isSending) {
            chatElements.sendButton.textContent = '停止';
            chatElements.messageInput.disabled = true;
            chatElements.typingIndicator.style.display = 'block';
            if (interpretBtn) interpretBtn.disabled = true;
            disablePresetButtons();
    
            // Set the cancel button listener
            chatElements.sendButton.addEventListener('click', () => {
                if (reader) {
                    reader.cancel();  // cancel the reader (stream)
                    chatElements.sendButton.textContent = '发送';  // reset text to "Send"
                    botMessageDiv.classList.remove('message-loading');
                    // Only append the "已中断" message once
                    if (!interruptedMessageAdded) {
                        responseContainer.textContent += ' (已中断)';
                        interruptedMessageAdded = true;
                    }
                }
            });
        } else {
            // Otherwise, restore button text and enable it
            chatElements.sendButton.textContent = '发送';
            chatElements.messageInput.disabled = false;
            chatElements.typingIndicator.style.display = 'none';
            if (interpretBtn) interpretBtn.disabled = false;
            chatElements.messageInput.focus();
            enablePresetButtons();
        }
    }

 
    
    // 禁用所有预设按钮
    function disablePresetButtons() {
        const buttons = presetButtonsContainer.querySelectorAll('button');
        buttons.forEach(button => {
            button.disabled = true;
            button.style.opacity = 0.5; // 设置按钮为灰色
        });
    }
    
    // 恢复预设按钮
    function enablePresetButtons() {
        const buttons = presetButtonsContainer.querySelectorAll('button');
        buttons.forEach(button => {
            button.disabled = false;
            button.style.opacity = 1; // 恢复按钮的正常样式
        });
    }
class AIChatComponent {
    constructor(container, options = {}) {
        this.container = container;
        this.questions = options.questions || [];
        this.render();
        window.chatRoot = this.container;
        this.initializeEvents();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="chat-container">
                <div class="chat-header">
                    建艺-建筑艺术大模型
                    <span class="ai-disclaimer">内容由AI生成，仅供参考</span>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="typing-indicator" id="typing-indicator">正在输入...</div>
                </div>
                <div class="chat-input">
                    <input type="text" id="message-input" placeholder="输入消息..." autofocus>
                    <button id="send-button">发送</button>
                    <button id="analysisImage">评价</button> 
                </div>
                
                <div class="preset-buttons">
                    ${this.questions.map(q => `<button>${q}</button>`).join('')}
                </div>
            </div>
            <img src="" class="ref_image_url" width="100" height="100" name="ref_image_url" id="ref_image_url" style="display: none;">
        `;
    }
    
    initializeEvents() {
        // 绑定所有事件
        const elements = {
            chatMessages: this.container.querySelector('#chat-messages'),
            messageInput: this.container.querySelector('#message-input'),
            sendButton: this.container.querySelector('#send-button'),
            analysisImageButton: this.container.querySelector('#analysisImage'),
            typingIndicator: this.container.querySelector('#typing-indicator'),
            presetButtons: this.container.querySelector('.preset-buttons')
        };
        
        // 初始化聊天功能
        initializeChat(elements);

        
        // 设置识物按钮
        this.container.querySelector('#analysisImage').addEventListener('click', () => {
            /*uploadOneImage('图片上传','#ref_image_url','ref_image_url','aichat',analysisImage,true,false);*/
            uploadOneImage('图片上传','#ref_image_url','ref_image_url','aichat',analysisObject,true,false);
        });
        
        // 设置预设问题按钮
        this.container.querySelectorAll('.preset-buttons button').forEach(btn => {
            btn.addEventListener('click', () => presetQuestion(btn.textContent));
        });
    }
}

// 暴露给全局环境
window.AIChatComponent = AIChatComponent;
    
        
