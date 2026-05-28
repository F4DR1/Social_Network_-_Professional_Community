// layout.js
import {
    authLogout,
    notificationsGetUnreadCount,
    chatsGetUnreadCount
} from './api.js?v=10';

document.addEventListener("DOMContentLoaded", function() {
    const profileLink = window.layoutData.profileLink;


    const profileDropdown = document.getElementById('profileDropdown');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const profileDropdownTrigger = document.getElementById('profileDropdownTrigger');
    const notificationsDropdownTrigger = document.getElementById('notificationsDropdownTrigger');


    const notificationsCounter = document.getElementById('notificationsCounter');
    const messagesCounter = document.getElementById('messagesCounter');


    let notificationsCount = 0;
    let messagesCount = 0;
    




    // Получает количество новых уведомлений из API и обновляет счётчик
    window.updateNotificationsCounter = async function() {
        console.log('Получаем количество непрочитанных уведомлений...');

        try {
            const result = await notificationsGetUnreadCount();

            notificationsCount = result.success ? result.count : notificationsCount + 1;
            notificationsCounter.innerHTML = notificationsCount === 0 ? '' : notificationsCount;
        } catch (err) {
            console.error('Произошла ошибка при обновлении счётчика напрочитанных уведомлений');
        }
    }

    // Получает количество новых сообщений из API и обновляет счётчик
    window.updateMessagesCounter = async function() {
        console.log('Получаем количество непрочитанных сообщений...');

        try {
            const result = await chatsGetUnreadCount();

            messagesCount = result.success ? result.count : messagesCount + 1;
            messagesCounter.innerHTML = messagesCount === 0 ? '' : messagesCount;
        } catch (err) {
            console.error('Произошла ошибка при обновлении счётчика напрочитанных чатов');
        }
    }



    // Устанавливает глобальные обработчики сообщений от WebSocket
    function setWebSocketHandlers() {
        // Показываем браузерное уведомление и обновляем счётчик непрочитанных уведомлений
        window.socket.on('new_notification_signal', (data) => {
            console.log(`Пришло новое уведомление "${data.notificationId}"`);
            window.updateNotificationsCounter();
            // showNotification(data.notificationId);  // Показать всплывающее окошко с новым уведомлением
            alert(`${data.type} \nПришло новое уведомление!`)
        });

        // Обновляем счётчик непрочитанных сообщений
        window.socket.on('new_message_signal', (data) => {
            console.log(`Пришло новое сообщение в чате "${data.chatId}"`)
            window.updateMessagesCounter();
        });
    }
    
    

    // Обновляем данные через API
    window.updateNotificationsCounter();
    window.updateMessagesCounter();

    // Включаем автоматическое обновление данные через WS
    if (window.socket)
        setWebSocketHandlers();
    else
        document.addEventListener('socketReady', setWebSocketHandlers);

    



    // =============== КНОПКИ ВЫПАДАЮЩЕГО МЕНЮ ПОЛЬЗОВАТЕЛЯ ===============
    // Профиль
    document.getElementById('profileDropdownProfile')?.addEventListener('click', async (e) => {
        e.preventDefault();
        window.location.href = `${window.APP_CONFIG.BASE_URL}/${profileLink}`;
    });

    // Настройки
    document.getElementById('profileDropdownSettings')?.addEventListener('click', async (e) => {
        e.preventDefault();
        window.location.href = `${window.APP_CONFIG.BASE_URL}/settings`;
    });

    // Выход из системы
    document.getElementById('profileDropdownLogout')?.addEventListener('click', async (e) => {
        e.preventDefault();

        try {
            const result = await authLogout();

            if (result.success) {
                window.location.href = window.APP_CONFIG.BASE_URL;
            } else {
                console.log(result.error || 'Ошибка выхода из системы');
            }

        } catch (err) {
            console.log(err.error);
        }
    });

    
    
    // =============== ОБРАБОТКА ВЫПАДАЮЩИХ МЕНЮ ПОЛЬЗОВАТЕЛЯ ===============
    // Выпадающий список текущего пользователя
    profileDropdownTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        console.log('profile clicked!');
        profileDropdown.toggleAttribute('open');
    });

    // Выпадающий список текущего пользователя
    notificationsDropdownTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        console.log('notifications clicked!');
        notificationsDropdown.toggleAttribute('open');
    });
    
    // Закрытие при клике вне
    document.addEventListener('click', () => {
        profileDropdown.removeAttribute('open');
        notificationsDropdown.removeAttribute('open');
    });
});
