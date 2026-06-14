// messenger.js
import {
    usersGetById,
    messagesGet, messagesMarkRead, messagesSend,
    chatsGet, chatsGetInfo, chatsGetIdByUser, chatsGetIdByGroup
} from '../api.js?v=5';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const currentUser = window.appData.currentUser;
    let chatSubType = window.appData.chatSubjectType;
    let chatSubId = window.appData.chatSubjectId;


    let activeChatId = null;  // id текущего активного чата
    let lastChatMessageId = -1;  // id последнего сообщения в чате
    let localMessageId = -1;  // Локальный id для отправленных сообщений в чат

    const typingUsers = {};  // Кто в каком чате печатает на данный момент



    const chatsList = document.getElementById('chatsList');

    const noChatPanel = document.getElementById('noChatPanel');
    const chatPanel = document.getElementById('chatPanel');

    const chatInfo = document.getElementById('chatInfo');
    const chatBackBtn = document.getElementById('chatBackButton');
    const chatMessages = document.getElementById('chatMessages');

    const activeChatTypingMessage = document.getElementById('typingMessage');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageButton');





    // -------------------- СТАТУСЫ --------------------
    const errorStatus = `
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
    `;
    const sendingStatus = `
        <polyline points="1 4 1 10 7 10"/>
        <polyline points="23 20 23 14 17 14"/>
        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
    `;
    const deliveredStatus = `
        <polyline points="16 8 9 17 4 12"/>
    `;
    const readStatus = `
        <polyline points="16 8 9 17 4 12"/>
        <polyline points="22 8 15 17 10 12"/>
    `;





    // -------------------- РАЗМЕТКА СООБЩЕНИЙ --------------------
    // Формирует разметку сообщения
    function formateMessageHTML(message) {
        const authorPhoto = message.author_photo;
        const authorName = message.sender_id === currentUser.id ? 'Вы' : message.author_name;
        const authorLinkname = message.author_linkname ?? `user${message.sender_id}`;
        const sentAt = message.sent_at;
        const updatedAt = message.updated_at;
        
        let messageStatusSVG = '';
        let messageStatusClass = '';
        if (message.sender_id == currentUser.id) {
            if ('isLocal' in message) {
                if ('sentError' in message) {
                    messageStatusClass = 'message-status-error';
                    messageStatusSVG = errorStatus;
                } else {
                    // Должна быть анимирована (для красоты) через CSS
                    messageStatusClass = 'message-status-sending';
                    messageStatusSVG = sendingStatus;
                }
            } else {
                const readsCount = message.reads_count;
                if (readsCount > 0) {
                    messageStatusClass = 'message-status-read';
                    messageStatusSVG = readStatus;
                } else {
                    messageStatusClass = 'message-status-delivered';
                    messageStatusSVG = deliveredStatus;
                }
            }
        }

        const messageId = message.id;
        const elementHTML = `
            <div class="message" data-message-id="${messageId}">
                <a class="message-author-photo" href="${authorLinkname}">
                    <img src="${authorPhoto}" alt="${authorName}" width="40" height="40">
                </a>
                <div class="message-main">
                    <div class="message-author-name"><a href="${authorLinkname}">${authorName}</a></div>
                    <div class="message-text">${message.text}</div>
                </div>
                <div class="message-info">
                    <time class="message-time" datetime="${sentAt}">${formatTime(sentAt)}</time>
                    <time class="message-date" datetime="${sentAt}">${formatDate(sentAt)}</time>
                    <div class="messageStatus">
                        <span class="message-status ${messageStatusClass}">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                ${messageStatusSVG}
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        `
        return {messageId, elementHTML};
    }
    
    // Обновляет список сообщений
    function updateMessages(messages) {
        if (messages && messages.length > 0) {
            // Выводим сообщения в чат
            messages.forEach(message => {
                const {messageId, elementHTML} = formateMessageHTML(message);
                if (chatMessages.querySelector(`.message[data-message-id="${messageId}"]`)) return;  // Пропускаем уже существующее сообщение в чате
                chatMessages.insertAdjacentHTML('beforeend', elementHTML);
                lastChatMessageId = message.id > lastChatMessageId ? message.id : lastChatMessageId;  // Находим id последнего сообщения
            });

        }
        
        if (!activeChatId && !chatSubId) return;
        
        const noMessageClass = 'welcome-chat-message';
        const hasMessages = chatMessages.children.length > 0 && !chatMessages.querySelector(`.${noMessageClass}`);
        
        if (!hasMessages) {
            chatMessages.innerHTML = `<h3 class="${noMessageClass}">Поздоровайтесь с пользователем!</h3>`;
        }
    }

    // Обновляем отправляемое сообщение
    function updateSendingMessage(message, previousMessageId = null) {
        const {messageId, elementHTML} = formateMessageHTML(message);  // Создаём новое сообщение

        if (previousMessageId === null) {
            // Старого сообщения нет - просто создаём сообщение
            chatMessages.insertAdjacentHTML('beforeend', elementHTML);

        } else {
            // Получаем старое сообщение
            console.log(`previousMessageId: ${previousMessageId}`);
            const previousMessageElement = chatMessages.querySelector(`.message[data-message-id="${previousMessageId}"]`);  // Старое сообщение (локальное)
            if (!previousMessageElement) return null;  // Проверка на всякий случай


            const isLocal = ('isLocal' in message);
            const isNewMessage = message.id > lastChatMessageId;
            if (isLocal || !isNewMessage) {
                console.log(`заменяем сообщение ${previousMessageId}`);
                // Замена старого сообщения на новое
                const temp = document.createElement('div');
                temp.innerHTML = elementHTML.trim();
                const newMessageElement = temp.firstChild;
                chatMessages.replaceChild(newMessageElement, previousMessageElement);

            } else {
                console.log(`удаляем сообщение ${previousMessageId} и создаём новое`);
                // Удаляем старое сообщение и загружаем все новые с сервера
                previousMessageElement.remove();
                getMessagesAPI();
            }
        }
        return messageId;
    }

    

    // -------------------- РАЗМЕТКА СПИСКА ЧАТОВ --------------------
    // Формирует разметку чата
    function formateChatHTML(chat) {
        const chatId = chat.id;
        const chatTitle = chat.chat_title;
        const chatPhoto = chat.chat_photo;
        const chatIsPrivate = chat.is_private;

        const unreadCount = chat.unread_count;

        const lastMessageSenderId = chat.last_message_sender_id;
        const lastMessageText = chat.last_message_text;
        const lastMessageTime = chat.last_message_time;
        const lastMessageAuthorId = chat.last_message_author_id;
        const lastMessageAuthorName = lastMessageAuthorId === currentUser.id ? 'Вы' : chat.last_message_author_name;
        const lastMessageAuthorPhoto = chat.last_message_author_photo;
        
        let messageStatusHTML = '';
        if (unreadCount > 0) {
            messageStatusHTML = `<span class="message-unread-count">${unreadCount}</span>`;
        } else {
            let messageStatusSVG = '';
            if (lastMessageSenderId == currentUser.id) {
                const lastMessageReadsCount = chat.last_message_reads_count;
                if (lastMessageReadsCount > 0) {
                    messageStatusSVG = readStatus;
                } else {
                    messageStatusSVG = deliveredStatus;
                }
            }
            messageStatusHTML = `
                <span class="message-read-status">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        ${messageStatusSVG}
                    </svg>
                </span>
            `;
        }

        const lastMessageHTML = `
            ${chatIsPrivate ? '' : `<img class="chat-last-message-author-photo" src="${lastMessageAuthorPhoto}" alt="${lastMessageAuthorName}" width="20" height="20">`}
            <div class="chat-last-message-main">
                <span class="chat-last-message-author-name">${lastMessageAuthorName}:</span>
                <span class="chat-last-message-text">${lastMessageText}</span>
            </div>
        `;
        
        const elementHTML = `
            <button class="chat" data-chat-id="${chatId}">
                <img class="chat-photo" src="${chatPhoto}" alt="${chatTitle}" width="60" height="60">
                <div class="chat-main">
                    <span class="chat-title">${chatTitle}</span>
                    <div class="chat-last-message">${lastMessageHTML}</div>
                    <span class="chat-typing-message"></span>
                </div>
                <div class="chat-info">
                    <time class="chat-last-message-time" datetime="${lastMessageTime}">${relativeTime(lastMessageTime, true)}</time>
                    <div class="chat-status">
                        ${messageStatusHTML}
                    </div>
                </div>
            </button>
        `;
        return {chatId, elementHTML};
    }

    // Обновляет список чатов
    function updateChatsList(chats) {
        chatsList.innerHTML = '';

        if (chats && chats.length > 0) {
            // Выводим чаты в список чатов
            chats.forEach(chat => {
                const {chatId, elementHTML} = formateChatHTML(chat);
                if (chatsList.querySelector(`.chat[data-chat-id="${chatId}"]`)) return;  // Пропускаем уже существующий чат в списке
                chatsList.insertAdjacentHTML('beforeend', elementHTML);
            });
            initChatsListActions();
        }
        
        const noChatsClass = 'no-chats';
        const hasChats = chatsList.children.length > 0 && !chatsList.querySelector(`.${noChatsClass}`);
        
        if (!hasChats) {
            chatsList.innerHTML = `<h3 class="${noChatsClass}">У вас нет ни одного чата.</h3>`;
        }
    }

    // Инициализация действий чатов в списке чатов
    function initChatsListActions() {
        // Открытие чата
        chatsList.querySelectorAll('.chat').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const chatId = btn.dataset.chatId;
                
                if (chatId == activeChatId) return;
                
                // Устанавливаем новый адрес (с поддержкой истории)
                const url = new URL(window.location);
                url.searchParams.set('type', 'chat');
                url.searchParams.set('id', chatId);
                window.history.pushState({}, '', url);
                await updateContent();
            });
        });
    }





    // -------------------- API ЗАПРОСЫ --------------------
    // Отправляем новое сообщение
    async function sendMessageAPI(message, previousMessageId = null) {
        previousMessageId = updateSendingMessage(message, previousMessageId);  // Временно показываем в чате локальное сообщение
        
        const data = {
            text: message.text.trim()
        };
        // Добавляем id чата или пользователя (для создания чата, если его нет)
        if (activeChatId !== null)
            data.chatId = activeChatId;
        else if (chatSubType === 'user')
            data.userId = chatSubId;

        try {
            const result = await messagesSend(data);

            if (result.success) {
                // Обновляем чат (если во время отправки сообщения его только создаём)
                if (activeChatId === null)
                    await getActiveChatId();
                updateSendingMessage(result.message, previousMessageId);  // Показываем отправленное сообщение
                updateChatList();  // Обновляем список чатов
                return;
            } else {
                console.log(result.error || 'Ошибка обработки сообщений');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }

        // Пытаемся отправить данные снова через некоторое время
        setTimeout(() => {
            if (!('sentError' in message)) message.sentError = true;  // Добавляем локальному сообщению ошибку об отправке
            sendMessageAPI(message, previousMessageId);
        }, 2000);
    }

    // Отмечаем что сообщение прочитано
    async function markReadMessagesAPI() {
        const data = {
            chatId: activeChatId
        };

        try {
            const result = await messagesMarkRead(data);

            if (result.success) {
                updateChatList();  // Обновляем список чатов
                window.updateMessagesCounter();  // Обновляем счётчик непрочитанных сообщений (метод из layout.js)
                return;
            } else {
                console.log(result.error || 'Ошибка обработки сообщений');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }

        // Пытаемся отправить данные снова через некоторое время
        setTimeout(() => {
            markReadMessagesAPI()
        }, 2000);
    }

    // Обновляем сообщения чата
    async function getMessagesAPI() {
        // В несуществующем чате нет сообщений
        if (!activeChatId) {
            updateMessages([]);
            return;
        }

        const data = {
            chatId: activeChatId
        }
        if (lastChatMessageId > -1) data.lastMessageId = lastChatMessageId;  // Обновляем с последнего сообщения
        
        try {
            const result = await messagesGet(data);

            if (result.success) {
                markReadMessagesAPI();  // Отмечаем сообщения как прочитанные
                await updateMessages(result.messages);
                return;
            } else {
                console.log(result.error || 'Ошибка обработки сообщений');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
    }

    // Обновляем данные всех чатов пользователя
    async function getChatsAPI() {
        try {
            const result = await chatsGet();

            if (result.success) {
                await updateChatsList(result.chats);
                return;
            } else {
                console.log(result.error || 'Ошибка обработки чатов');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
        updateChatsList([]);
    }

    // Получаем информацию по текущему активному чату
    async function getActiveChatInfoAPI() {
        const data = {
            type: chatSubType
        }
        switch (chatSubType) {
            case 'chat':
                data.chatId = activeChatId;
                break;
                
            case 'user':
                data.userId = chatSubId;
                break;
                
            case 'group':
                data.groupId = chatSubId;
                break;
        
            default:
                return;
        }
        
        try {
            const result = await chatsGetInfo(data);

            if (result.success) {
                return result.chat;
            } else {
                console.log(result.error || 'Ошибка обработки чатов');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }

        return null;
    }
    
    // Получаем id текущего активного чата
    async function getActiveChatId() {
        let chatId = null;
        try {
            let result;
            switch (chatSubType) {
                case 'group':
                    result = await chatsGetIdByGroup(chatSubId);
                    break;
                    
                case 'user':
                    result = await chatsGetIdByUser(chatSubId);
                    break;
                    
                case 'chat':
                    activeChatId = chatSubId;
                    return;
            
                default:
                    return;
            }

            if (result.success) {
                chatId = result.chatId;
            } else {
                console.log(result.error || 'Ошибка обработки чатов');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
        activeChatId = chatId;


        if (!activeChatId) return;

        // Устанавливаем новый адрес (без поддержки истории)
        const url = new URL(window.location);
        url.searchParams.set('type', 'chat');
        url.searchParams.set('id', activeChatId);
        window.history.replaceState({}, '', url);
    }





    // -------------------- ВИЗУАЛЬНАЯ ОБРАБОТКА --------------------
    // Обновляем состояние кнопки отправки сообщения
    function sendMessageButtonStateUpdate() {
        if (!messageInput || !sendMessageBtn) return;

        const hasText = messageInput.value.trim().length > 0;
        // sendMessageBtn.classList.toggle('active', hasText);
        sendMessageBtn.disabled = !hasText;
    }

    // Возращает строку печатающих в чате пользователей
    function typingMessage(chatId) {
        const users = typingUsers[chatId] || [];
        const names = users.map(u => u.name);

        if (users.length === 0) return '';
        if (users.length === 1) return `${names[0]} печатает...`;
        if (users.length === 2) return `${names[0]} и ${names[1]} печатают...`;
        return `${names[0]}, ${names[1]} и ещё ${names.length - 2} печатают...`;
    }

    // Индикатор кто печатает в чате
    async function typingIndicator(data) {
        const type = data.type;
        const chatId = data.chatId;
        const userId = data.userId;


        const chatButton = chatsList.querySelector(`.chat[data-chat-id="${chatId}"]`);
        if (!chatButton) return;
        const chatTypingMessage = chatButton.querySelector('.chat-typing-message');
        if (!chatTypingMessage) return;


        // Создаём переменную печатающих (если нет)
        if (!typingUsers[chatId]) {
            typingUsers[chatId] = [];
        }


        // Обработка события печатания
        switch (type) {
            case 'typing_start':
                // Если кто-то начал печатать - заносим его в переменную
                const exists = typingUsers[chatId].some(user => user.id === userId);
                if (!exists) {
                    // Получаем имя пользователя
                    let userName = `user${userId}`;
                    try {
                        const result = await usersGetById(userId);
                        if (result.success) {
                            const user = result.user;
                            userName = user.fullname;
                        } else {
                            console.log(result.error || 'Ошибка обработки чатов');
                        }
                    } catch (err) {
                        console.error('Ошибка сервера');
                    }
                    typingUsers[chatId].push({ id: userId, name: userName });
                }
                break;
            
            case 'typing_end':
                // Если кто-то перестал печатать - удаляем его из переменной
                if (typingUsers[chatId]) {
                    typingUsers[chatId] = typingUsers[chatId].filter(user => user.id !== userId);
                    // Если массив опустел, можно удалить ключ, чтобы не накапливались пустые записи
                    if (typingUsers[chatId].length === 0) {
                        delete typingUsers[chatId];
                    }
                }
                break;
            
            default:
                return;
        }
        
        const msg = typingMessage(chatId);
        chatTypingMessage.innerHTML = msg;
        chatTypingMessage.classList.toggle('active', chatTypingMessage.innerHTML.length > 0);

        if (chatId === activeChatId) {
            activeChatTypingMessage.innerHTML = msg;
            activeChatTypingMessage.classList.toggle('active', activeChatTypingMessage.innerHTML.length > 0);
        }
    }
    
    // Отобразить разметку активного чата
    async function showActiveChatHTML(isActiveChat) {
        // Состояние кнопки отправки сообщения
        messageInput.value = '';
        sendMessageButtonStateUpdate();

        // Панель чата
        noChatPanel.classList.toggle('active', !isActiveChat);
        chatPanel.classList.toggle('active', isActiveChat);


        // Устанавливаем информацию о чате
        const chat = await getActiveChatInfoAPI();  // Получаем информацию о чате

        const title = chat ? chat.chat_title : '';
        const photo = chat ? chat.chat_photo : '';
        const membersCount = chat ? chat.chat_members_count : '';
        const isPrivate = chat ? chat.is_private : true;

        const chatImage = chatInfo.querySelector('.chat-info-image');
        const chatTitle = chatInfo.querySelector('.chat-info-title');
        const chatMembersCount = chatInfo.querySelector('.chat-info-members-count');

        chatImage.src = photo;
        chatImage.alt = title;
        chatTitle.textContent = title;
        chatMembersCount.textContent = isPrivate ? '' : membersCount;
    }

    // Обновляем время последних сообщений всех чатов
    function updateLastMessageTimes() {
        document.querySelectorAll('.chat').forEach(btn => {
            const chatLastMessageTime = btn.querySelector('.chat-last-message-time');
            chatLastMessageTime.textContent = relativeTime(chatLastMessageTime.dateTime, true);
        });

        setTimeout(updateLastMessageTimes, 30000);
    }



    // -------------------- СТАРТОВЫЕ МЕТОДЫ --------------------
    // Обновить список чатов
    async function updateChatList() {
        getChatsAPI();  // Загружаем сообщения в чате
    }

    // Обновить активный чат
    async function updateActiveChat() {
        await getActiveChatId();  // Получаем id активного чата (если чата нет - null)
        
        showActiveChatHTML((activeChatId !== null || chatSubId !== null));  // Отображаем разметку чата и его информацию

        await getMessagesAPI();  // Загружаем сообщения в чате

        
        // Листаем чат
        if (!chatMessages) return;
        
        const savedPosition = sessionStorage.getItem('scrollPosition');
        if (savedPosition) {
            // Скроллим к прошлой позиции
            chatMessages.scrollTop = parseInt(savedPosition, 10);
            sessionStorage.removeItem('scrollPosition');
        } else {
            // Скроллим к концу
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }


    
    // -------------------- СТАРТОВАЯ ОБРАБОТКА --------------------
    // Устанавливает глобальные обработчики сообщений от WebSocket
    function setWebSocketHandlers() {

        // Обработчики событий чата
        window.socket.on('typing_start', typingIndicator);
        window.socket.on('typing_end', typingIndicator);


        // Обработчик обновления сообщений
        window.socket.on('new_message_signal', (data) => {
            // Обновляем список чатов
            updateChatList();

            // Обновляем сообщения в открытом чате
            if (data.chatId === activeChatId) {
                getMessagesAPI();
            }
        });


        // Отправка индикатора печати
        let typingTimer;
        messageInput.addEventListener('input', function() {
            // Авторесайз textarea
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 5 + 'px';

            // Изменяем состояние кнопки в зависимости от заполненности поля
            sendMessageButtonStateUpdate();

            const hasText = messageInput.value.trim().length > 0;
            if (!activeChatId || !hasText) return;

            if (!typingTimer) {
                window.socket.send({ type: 'typing_start', chatId: activeChatId });
            }

            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                window.socket.send({ type: 'typing_end', chatId: activeChatId });
                typingTimer = null;
            }, 2000);
        });
    }

    // Вызов стартовых методов для настройки панелей чата и списка чатов
    async function startUpdateData() {
        updateChatList();  // Обновить список чатов
        updateActiveChat();  // Обновить активный чат
    }



    (async () => {
        // Обновляем данные
        await startUpdateData();

        // Сохраняем позицию скролла перед уходом со страницы
        window.addEventListener('beforeunload', () => {
            if (activeChatId !== null || chatSubId !== null) sessionStorage.setItem('scrollPosition', chatMessages.scrollTop.toString());
        });


        // Обновлять время сообщений у чатов
        updateLastMessageTimes();

        // Включаем автоматическое обновление данных через WS
        if (window.socket)
            setWebSocketHandlers();
        else
            document.addEventListener('socketReady', setWebSocketHandlers);
    })();





    // -------------------- АДРЕС --------------------
    // Обработка нового адреса страницы
    async function updateContent() {
        lastChatMessageId = -1;  // Обнуляем переменную id последнего сообщения
        chatMessages.innerHTML = '';
        
        const url = new URL(window.location.href);

        // Получаем query-параметры
        const type = url.searchParams.get('type');  // Тип чата из адреса
        const id = url.searchParams.get('id');  // id из адреса


        // Устанавливаем новые данные чата
        chatSubType = type;
        chatSubId = id;
        activeChatId = chatSubId;
        
        // Обновляем данные
        startUpdateData();
    }

    // При возвращении по истории
    window.addEventListener('popstate', async () => {
        await updateContent();
    });



    // Закрыть открытый чат
    chatBackBtn.addEventListener('click', async (e) => {
        if (!activeChatId && !chatSubId) return;
        
        // Устанавливаем новый адрес (с поддержкой истории)
        const url = new URL(window.location);
        url.search = '';
        window.history.pushState({}, '', url);
        await updateContent();
    });

    messageInput.addEventListener('keydown', function(event) {
        // Проверяем: нажат Enter И НЕ нажат Shift
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();  // Не даём браузеру добавить новую строку

            // Если кнопка отправки существует и не заблокирована – кликаем по ней
            if (sendMessageBtn && !sendMessageBtn.disabled) {
                sendMessageBtn.click();
            }
        }
        // Иначе: Shift+Enter или любой другой Enter с Shift – ничего не делаем,
        // браузер выполнит стандартное поведение (вставка переноса строки)
    });
    
    // Отправить сообщение
    sendMessageBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        
        const hasText = messageInput.value.trim().length > 0;
        if (hasText) {
            const messageText = messageInput.value.trim();
            messageInput.value = '';

            // Отправляем в чат локальное сообщение
            const message = {
                id: localMessageId,
                chat_id: activeChatId,
                sender_id: currentUser.id,
                text: messageText,
                sent_at: Date.now(),
                updated_at: Date.now(),
                author_linkname: currentUser.linknamek,
                author_name: 'Вы',
                author_photo: currentUser.photo ?? null,
                isLocal: true
            }
            await sendMessageAPI(message);
            localMessageId -= 1;
            
            // Листаем чат в конец
            if (!chatMessages) return;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
});
