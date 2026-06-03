// layout.js
import {
    authLogout,
    usersGetById,
    notificationsGetUnreadCount, notificationsGet, notificationsMarkRead,
    chatsGetUnreadCount
} from './api.js?v=16';

document.addEventListener("DOMContentLoaded", function() {
    const userIsAuthorized = window.layoutData.userIsAuthorized;
    const profileLink = window.layoutData.profileLink;


    const profileDropdown = document.getElementById('profileDropdown');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const profileDropdownTrigger = document.getElementById('profileDropdownTrigger');
    const notificationsDropdownTrigger = document.getElementById('notificationsDropdownTrigger');


    const notificationsCounter = document.getElementById('notificationsCounter');
    const messagesCounter = document.getElementById('messagesCounter');


    const notificationsList = document.getElementById('notificationsList');


    let notificationsCount = 0;
    let messagesCount = 0;
    




    // -------------------- СЧЁТЧИКИ --------------------
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

    



    // =============== ОБНОВЛЕНИЕ СПИСКА УВЕДОМЛЕНИЙ ===============
    // Формирует разметку уведомления
    async function formateNotificationHTML(notification) {
        const notificationId = notification.id;

        const notificationType = notification.type;
        const notificationCameTime = notification.created_at;
        const notificationData = JSON.parse(notification.data);

        let typeClass = '';
        let placeholder = '';
        
        try {
            switch (notificationType) {
                case 'new_subscriber':
                    const userId = notificationData.actor_id;
                    const notificationMessage = notificationData.message;

                    const result = await usersGetById(userId);
                    if (result.success) {
                        const user = result.user;
                        const userLink = user.linkname ?? `user${user.id}`;

                        typeClass = 'new-subscriber';
                        placeholder = `
                            <div class="notification-photo">
                                <a class="user-photo" href="${userLink}"><img src="${user.photo}" alt="${user.fullname}" width="40" height="40"></a>
                            </div>
                            <div class="notification-main">
                                <h3 class="notification-title">Новый контакт</h3>
                                <p class="notification-message">Пользователь <a class="user-name" href="${userLink}">${user.fullname}</a> ${notificationMessage}</p>
                            </div>
                            <div class="notification-info">
                                <time class="notification-date" datetime="${notificationCameTime}">${relativeTime(notificationCameTime)}</time>
                            </div>
                        `;
                    } else {
                        console.log(result.error || 'Ошибка обработки уведомления');
                    }
                    break;
            
                default:
                    typeClass = 'none';
                    placeholder = `
                        <p>Не распознать уведомление.</p>
                    `;
                    break;
            }
        } catch (err) {
            console.error(`Ошибка формирования уведомления с id='${notificationId}'`);
        }

        
        const elementHTML = `
            <div class="notification ${typeClass}-type" data-notification-id="${notificationId}">
                ${placeholder}
            </div>
        `;
        return {notificationId, elementHTML};
    }

    // Обновляет список уведомлений
    async function notificationsListUpdate(notifications) {
        notificationsList.innerHTML = '';

        if (notifications && notifications.length > 0) {
            // Выводим чаты в список чатов
            for (const notification of notifications) {
                const {notificationId, elementHTML} = await formateNotificationHTML(notification);
                if (notificationsList.querySelector(`.notification[data-notification-id="${notification}"]`)) continue;  // Пропускаем уже существующее уведомление в списке
                notificationsList.insertAdjacentHTML('beforeend', elementHTML);
            };
        }
        
        const noNotificationsClass = 'no-notifications';
        const hasNotifications = notificationsList.children.length > 0 && !notificationsList.querySelector(`.${noNotificationsClass}`);
        
        if (!hasNotifications) {
            notificationsList.innerHTML = `<h3 class="${noNotificationsClass}">У вас пока нет уведомлений.</h3>`;
        }
    }





    // =============== КНОПКИ ВЫПАДАЮЩЕГО МЕНЮ ПОЛЬЗОВАТЕЛЯ ===============
    // Обновляем уведомления
    async function getNotificationsAPI() {
        try {
            const result = await notificationsGet();

            if (result.success) {
                notificationsListUpdate(result.notifications);
                markReadNotificationsAPI();
            } else {
                console.log(result.error || 'Ошибка обработки уведомлений');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
    }

    // Отмечаем что уведомления прочитаны
    async function markReadNotificationsAPI() {
        try {
            const result = await notificationsMarkRead();

            if (result.success) {
                window.updateNotificationsCounter();
                return;
            } else {
                console.log(result.error || 'Ошибка обработки уведомлений');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }

        // Пытаемся отправить данные снова через некоторое время
        setTimeout(() => {
            markReadNotificationsAPI()
        }, 2000);
    }





    // =============== СТАРТОВАЯ НАСТРОЙКА ===============
    // Устанавливает глобальные обработчики сообщений от WebSocket
    function setWebSocketHandlers() {
        // Показываем браузерное уведомление и обновляем счётчик непрочитанных уведомлений
        window.socket.on('new_notification_signal', (data) => {
            console.log(`Пришло новое уведомление "${data.notificationId}"`);
            window.updateNotificationsCounter();
            // showNotification(data.notificationId);  // Показать всплывающее окошко с новым уведомлением
            alert(`Пришло новое уведомление!\nЭта панель временно замещает всплывающее окно...`);
        });

        // Обновляем счётчик непрочитанных сообщений
        window.socket.on('new_message_signal', (data) => {
            console.log(`Пришло новое сообщение в чате "${data.chatId}"`)
            window.updateMessagesCounter();
        });
    }
    
    

    // Начальная настройка счётчиков (только если авторизован)
    if (userIsAuthorized) {
        // Обновляем данные через API
        window.updateNotificationsCounter();
        window.updateMessagesCounter();

        // Включаем автоматическое обновление данные через WS
        if (window.socket)
            setWebSocketHandlers();
        else
            document.addEventListener('socketReady', setWebSocketHandlers);
    }





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
        profileDropdown?.toggleAttribute('open');
    });

    // Выпадающий список текущего пользователя
    notificationsDropdownTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationsDropdown?.toggleAttribute('open');
        getNotificationsAPI(); // Показываем уведомления и прочитываем их
    });
    
    // Закрытие при клике вне
    document.addEventListener('click', () => {
        profileDropdown?.removeAttribute('open');
        notificationsDropdown?.removeAttribute('open');
    });
});
