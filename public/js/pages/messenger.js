import {
    usersGetById,
    messagesGet, messagesMarkRead, messagesSend,
    chatsGet, chatsGetInfo, chatsGetIdByUser, chatsGetIdByGroup
} from '../api.js?v=5';

document.addEventListener('DOMContentLoaded', function() {
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



    const chatsList = document.getElementById('chatsList');

    const noChatPanel = document.getElementById('noChatPanel');
    const chatPanel = document.getElementById('chatPanel');

    const chatInfo = document.getElementById('chatInfo');
    const chatBackBtn = document.getElementById('chatBackButton');
    const chatMessages = document.getElementById('chatMessages');

    const typingMessage = document.getElementById('typingMessage');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageButton');


    


    // -------------------- ДАТЫ И ВРЕМЯ --------------------
    // Форматирование даты
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        
        const day = date.getDate();
        const month = date.toLocaleDateString('ru-RU', { month: 'long' });
        const year = date.getFullYear();
        
        if (year === now.getFullYear()) {
            return `${day} ${month}`;
        } else {
            return `${day} ${month} ${year}`;
        }
    }

    // Формирование времени
    function formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Сколько времени прошло
    function relativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;  // Разница в миллисекундах

        // Будущее время – просто показываем абсолютную дату
        if (diffMs < 0) {
            return formatDate(date);
        }

        const diffSeconds = Math.floor(diffMs / 1000);
        if (diffSeconds < 60) {
            return 'только что';
        }

        const diffMinutes = Math.floor(diffSeconds / 60);
        if (diffMinutes < 60) {
            return `${diffMinutes}м`;
        }

        const diffHours = Math.floor(diffMinutes / 60);
        if (diffHours < 24) {
            return `${diffHours}ч`;
        }

        const diffDays = Math.floor(diffHours / 24);
        if (diffDays < 7) {
            return `${diffDays}д`;
        }

        const diffWeeks = Math.floor(diffDays / 7);
        if (diffWeeks <= 4) {
            return `${diffWeeks}н`;
        }

        // Более 4 недель – абсолютная дата
        return formatDate(date);
    }





    // -------------------- РАЗМЕТКА СООБЩЕНИЙ --------------------
    // Формирует разметку сообщения
    function formateMessageHTML(message) {
        const authorPhoto = message.author_photo;
        const authorName = message.sender_id === currentUser.id ? 'Вы' : message.author_name;
        const authorLinkname = message.author_linkname ?? `user${message.sender_id}`;
        const sent_at = message.sent_at;
        const updated_at = message.updated_at;
        
        let messageStatus = '';
        if ('isLocal' in message) {
            messageStatus = 'sentError' in message ? '❌' : '🔄';
        } else {
            messageStatus = '✅';
        }

        const elementId = `chatMessage${message.id}`;
        const elementHTML = `
            <div class="message" id="${elementId}" data-message-id="${message.id}">
                <a class="message-author-photo" href="${authorLinkname}">
                    <img src="${authorPhoto}" alt="${authorName}" width="40" height="40">
                </a>
                <div class="message-main">
                    <div class="message-author-name"><a href="${authorLinkname}">${authorName}</a></div>
                    <div class="message-text">${message.text}</div>
                </div>
                <div class="message-info">
                    <time class="message-time" datetime="${sent_at}">${formatTime(sent_at)}</time>
                    <time class="message-date" datetime="${sent_at}">${formatDate(sent_at)}</time>
                    <div class="messageStatus">
                        ${messageStatus}
                    </div>
                </div>
            </div>
        `
        return {elementId, elementHTML};
    }
    
    // Обновляет список сообщений
    function updateMessages(messages) {
        if (messages && messages.length > 0) {
            // Выводим сообщения в чат
            messages.forEach(message => {
                const {elementId, elementHTML} = formateMessageHTML(message);
                if (document.getElementById(elementId)) return;  // Пропускаем уже существующее сообщение в чате
                chatMessages.insertAdjacentHTML('beforeend', elementHTML);
                lastChatMessageId = message.id > lastChatMessageId ? message.id : lastChatMessageId;  // Находим id последнего сообщения
            });

        }
        
        if (!activeChatId) return;
        
        const noMessageClass = 'welcome-chat-message';
        const hasMessages = chatMessages.children.length > 0 && !chatMessages.querySelector(`.${noMessageClass}`);
        
        if (!hasMessages) {
            chatMessages.innerHTML = `<h3 class="${noMessageClass}">Поздоровайтесь с пользователем!</h3>`;
        }
    }

    // Обновляем отправляемое сообщение
    function updateSendingMessage(message, previousElementId = null) {
        const {elementId, elementHTML} = formateMessageHTML(message);  // Создаём новое сообщение

        if (previousElementId === null) {
            // Старого сообщения нет - просто создаём сообщение
            chatMessages.insertAdjacentHTML('beforeend', elementHTML);

        } else {
            // Старое сообщение есть
            
            // Получаем старое сообщение
            const previousMessageElement = document.getElementById(previousElementId);  // Старое сообщение (локальное)
            if (!previousMessageElement) return null;  // Проверка на всякий случай


            const isLocal = ('isLocal' in message);
            const isNewMessage = message.id > lastChatMessageId;
            if (isLocal || !isNewMessage) {
                // Замена старого сообщения на новое
                const temp = document.createElement('div');
                temp.innerHTML = elementHTML.trim();
                const newMessageElement = temp.firstChild;
                chatMessages.replaceChild(newMessageElement, previousMessageElement);
                return elementId;

            } else {
                // Удаляем старое сообщение и загружаем все новые с сервера
                previousMessageElement.remove();
                getMessagesAPI();
            }
        }
        return elementId;
    }

    

    // -------------------- РАЗМЕТКА СПИСКА ЧАТОВ --------------------
    // Формирует разметку чата
    function formateChatHTML(chat) {
        const chatId = chat.id;
        const chatTitle = chat.chat_title;
        const chatPhoto = chat.chat_photo;
        const chatIsPrivate = chat.is_private;

        const unreadCount = chat.unread_count;

        const lastMessageText = chat.last_message_text;
        const lastMessageTime = chat.last_message_time;
        const lastMessageAuthorId = chat.last_message_author_id;
        const lastMessageAuthorName = lastMessageAuthorId === currentUser.id ? 'Вы' : chat.last_message_author_name;
        const lastMessageAuthorPhoto = chat.last_message_author_photo;
        
        let messageStatusHTML = '';
        if (unreadCount > 0) {
            messageStatusHTML = `<span class="message-unread-count">${unreadCount}</span>`;
        } else {
            messageStatusHTML = `<span class="message-read-status">✅</span>`;
        }

        const lastMessageHTML = `
            ${chatIsPrivate ? '' : `<img class="chat-last-message-author-photo" src="${lastMessageAuthorPhoto}" alt="${lastMessageAuthorName}" width="20" height="20">`}
            <div class="chat-last-message-main">
                <span class="chat-last-message-author-name">${lastMessageAuthorName}:</span>
                <span class="chat-last-message-text">${lastMessageText}</span>
            </div>
        `;
        
        const elementId = `chat${chatId}`;
        const elementHTML = `
            <button class="chat" id="${elementId}" data-chat-id="${chatId}">
                <img class="chat-photo" src="${chatPhoto}" alt="${chatTitle}" width="60" height="60">
                <div class="chat-main">
                    <span class="chat-title">${chatTitle}</span>
                    <div class="chat-last-message">${lastMessageHTML}</div>
                </div>
                <div class="chat-info">
                    <time class="chat-last-message-time" datetime="${lastMessageTime}">${relativeTime(lastMessageTime)}</time>
                    <div class="chat-status">
                        ${messageStatusHTML}
                    </div>
                </div>
            </button>
        `;
        return {elementId, elementHTML};
    }

    // Обновляет список чатов
    function updateChatsList(chats) {
        chatsList.innerHTML = '';

        if (chats && chats.length > 0) {
            // Выводим чаты в список чатов
            chats.forEach(chat => {
                const {elementId, elementHTML} = formateChatHTML(chat);
                if (document.getElementById(elementId)) return;  // Пропускаем уже существующее сообщение в чате
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
    async function sendMessageAPI(message, previousElementId = null) {
        previousElementId = updateSendingMessage(message, previousElementId);  // Временно показываем в чате локальное сообщение
        
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
                updateSendingMessage(result.message, previousElementId);  // Показываем отправленное сообщение
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
            sendMessageAPI(message, previousElementId);
        }, 2000);
    }

    // Отмечаем что сообщение прочитано
    async function markReadAPI() {
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
            markReadAPI()
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
                markReadAPI();  // Отмечаем сообщения как прочитанные
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
        if (chatSubType === 'chat') data.chatId = activeChatId;
        if (chatSubType === 'user') data.userId = chatSubId;
        if (chatSubType === 'group') data.groupId = chatSubId;
        
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
        sendMessageBtn.classList.toggle('active', hasText);
        sendMessageBtn.disabled = !hasText;
    }

    // Индикатор кто печатает в чате
    async function typingIndicator(data) {
        const type = data.type;
        const chatId = data.chatId;
        const userId = data.userId;

        if (chatId !== activeChatId) return;

        switch (type) {
            case 'typing_start':
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

                typingMessage.innerHTML = `${userName} печатает...`;
                typingMessage.classList.add('active');
                break;
            
            case 'typing_end':
                typingMessage.innerHTML = '';
                typingMessage.classList.remove('active');
                break;
            
            default:
                return;
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
            chatLastMessageTime.textContent = relativeTime(chatLastMessageTime.dateTime);
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
        
        showActiveChatHTML((activeChatId !== null));  // Отображаем разметку чата и его информацию

        getMessagesAPI();  // Загружаем сообщения в чате
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
        startUpdateData();

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
    chatBackBtn?.addEventListener('click', async (e) => {
        if (!activeChatId) return;
        
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
            sendMessageAPI(message);
            localMessageId -= 1;
        }
    });
});
