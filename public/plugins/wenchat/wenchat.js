          chatMessages = null;
          messageInput = null;
          sendButton = null;
          typingIndicator = null;
        const API_KEY = 'sk-592cc227fd1b460dab036ff2d7d5dbe6';
        const API_URL = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
        const MAX_TURNS = 3;
         conversationHistory = [];

        // 初始化的默认提示内容
        const presetContent = `你是一个专业的文博专家，你会回答任何与文博领域相关的问题。`;





    // 动态创建聊天框HTML结构
    function createChatBox() {
        const chatContainer = document.createElement('div');
        chatContainer.className = 'chat-container';
        chatContainer.innerHTML = `
            <div class="chat-header">问艺-文博工艺美术大模型</div>
            <div class="chat-messages" id="chat-messages">
                <div class="typing-indicator" id="typing-indicator">通义千问正在输入...</div>
            </div>
            <div class="chat-input">
                <input type="text" id="message-input" placeholder="输入消息..." autofocus>
                <button id="send-button">发送</button>
            </div>
            <div class="preset-buttons">
                <button onclick="presetQuestion('给我介绍明清瓷器的特点')">给我介绍明清瓷器的特点</button>
                <button onclick="presetQuestion('青铜器都有哪些器型？')">青铜器都有哪些器型？</button>
                <button onclick="presetQuestion('古代玉器都有哪些纹样')">古代玉器都有哪些纹样？</button>
            </div>
        `;
        document.body.appendChild(chatContainer);

          chatMessages = document.getElementById('chat-messages');
          messageInput = document.getElementById('message-input');
          sendButton = document.getElementById('send-button');
          typingIndicator = document.getElementById('typing-indicator');



        // 添加按钮事件
        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
        // 发送消息的函数
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (message === '') return;

            messageInput.disabled = true;
            sendButton.disabled = true;

            addMessage(message, true);
            messageInput.value = '';
            typingIndicator.style.display = 'block';
            chatMessages.scrollTop = chatMessages.scrollHeight;

            try {
                const reader = await callAPI(message);
                typingIndicator.style.display = 'none';
                await handleStreamResponse(reader);
            } catch (error) {
                typingIndicator.style.display = 'none';
                addMessage('抱歉，获取回复时出错: ' + error.message, false);
            } finally {
                messageInput.disabled = false;
                sendButton.disabled = false;
                messageInput.focus();
            }
        }

        // 添加消息到聊天界面
        function addMessage(text, isUser) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message');
            messageDiv.classList.add(isUser ? 'user-message' : 'bot-message');
            messageDiv.textContent = text;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // 处理流式响应
        async function handleStreamResponse(reader) {
            const decoder = new TextDecoder();
            let botMessageDiv = document.createElement('div');
            botMessageDiv.classList.add('message', 'bot-message', 'message-loading');
            chatMessages.appendChild(botMessageDiv);

            let fullResponse = '';
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value);
                const lines = chunk.split('\n').filter(line => line.trim() !== '');
                for (const line of lines) {
                    if (line.startsWith('data:')) {
                        try {
                            const data = JSON.parse(line.substring(5).trim());
                            const content = data.choices[0].delta?.content;
                            if (content) {
                                fullResponse += content;
                                botMessageDiv.innerHTML = marked.parse(fullResponse);
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                            }

                            if (data.choices[0].finish_reason === 'stop') {
                                conversationHistory.push({ role: 'assistant', content: fullResponse });
                                break;
                            }
                        } catch (e) {
                            console.error('解析JSON失败:', e);
                        }
                    }
                }
            }
        }

        // 调用API
        async function callAPI(message) {
            const messagesToSend = [
                { role: "system", content: presetContent },
                ...conversationHistory.slice(-MAX_TURNS),
                { role: "user", content: message }
            ];

            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${API_KEY}`
                },
                body: JSON.stringify({
                    model: "qwen-plus",
                    messages: messagesToSend,
                    type: 'text',
                    stream: true
                })
            });

            if (!response.ok) {
                throw new Error(`API请求失败: ${response.status}`);
            }

            return response.body.getReader();
        }

        // 预设问题
        function presetQuestion(question) {
            document.getElementById('message-input').value = question;
            sendMessage();
        }    

