import {
    sessionsGet, sessionsTerminate, sessionsTerminateAllOther
} from '../api.js';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    // Базовые данные
    const userId = window.appData.userId;


    
    let currentCategory = 'sessions';


    
    const editSettingsDataCategoryBtnsPanel = document.getElementById('editSettingsDataCategoryButtonsPanel');
    const editSettingsDataPanel = document.getElementById('editSettingsDataPanel');


    // Название категории
    const categoryTitle = editSettingsDataPanel.querySelector('.container-title');

    // Панель сообщения
    const messagePanel = editSettingsDataPanel.querySelector('.message-panel');
    const messageIcon = messagePanel.querySelector('.message-icon');
    const messageTitle = messagePanel.querySelector('.message-title');
    const messageText = messagePanel.querySelector('.message-text');


    
    // Элементы категории сессий
    const sessionsDataCategoryBtn = editSettingsDataCategoryBtnsPanel.querySelector('.category-sessions')
    const sessionsDataPanel = editSettingsDataPanel.querySelector('.sessions-data');
    const sessionsList = sessionsDataPanel.querySelector('.sessions-list');



    
    // =============== ПАНЕЛЬ СООБЩЕНИЯ ===============
    // Переключаем видимость сообщения
    function setMessageVisible(isVisible = false) {
        messagePanel.classList.toggle('active', isVisible);
    }

    // Отображаем сообщение
    function updateMessage(msg, status = 'error') {
        messagePanel.classList.remove('success');
        messagePanel.classList.remove('error');

        messagePanel.classList.add(status);
        
        switch (status) {
            case 'success':
                // Зелёная галочка
                messageIcon.innerHTML = `
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                    <polyline points="8,12 11,15 16,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                `;
                messageTitle.innerHTML = 'Изменения сохранены';
                break;
        
            case 'error':
            default:
                // Красный крестик
                messageIcon.innerHTML = `
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                    <line x1="8" y1="8" x2="16" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <line x1="16" y1="8" x2="8" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                `;
                messageTitle.innerHTML = 'Ошибка обновления данных';
                break;
        }
        messageText.innerHTML = msg;
        setMessageVisible(true);
    }





    // =============== СПИСОК СЕССИЙ ===============
    // Формирует разметку сообщения
    function formateSessionHTML(session) {
        const sessionId = session.id;
        const createdAt = session.created_at;
        
        const ipAddress = session.ip_address;

        const deviceName = session.device_name;
        const deviceType = session.device_type;
        const deviceTypePhoto = session.device_type_photo;

        const lastActivity = session.last_activity;
        const lastActivityHuman = session.last_activity_human;

        const isCurrent = session.is_current;
        

        const deleteSessionBtn = isCurrent ? '' : `
            <button class="session-delete-btn">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="5" x2="19" y2="19"/>
                    <line x1="5" y1="19" x2="19" y2="5"/>
                </svg>
            </button>
        `;

        const elementHTML = `
            <div class="session" data-session-id="${sessionId}">

                <div class="session-main">
                    <img class="session-photo" src="${deviceTypePhoto}" alt="${deviceType}" width="40" height="40">
                    <div class="session-text">
                        <div class="session-device-info">
                            <div class="session-device-name">${deviceName}</div>
                            <div class="session-device-type">${deviceType}</div>
                        </div>
                        <div class="session-info">
                            <div class="session-device-ip">${ipAddress}</div>
                            <time class="session-last-activity-time" datetime="${lastActivity}">${lastActivityHuman}</time>
                        </div>
                    </div>
                    <div class="session-is-active">

                    </div>
                </div>
                ${deleteSessionBtn}

            </div>
        `;
        return {sessionId, isCurrent, elementHTML};
    }

    // Обновляет список сессий
    function updateSessionsList(sessions) {
        sessionsList.innerHTML = '';
        if (sessions && sessions.length > 0) {
            // Выводим сессии в список
            let sessionsHTML = '';
            sessions.forEach(session => {
                const {sessionId, isCurrent, elementHTML} = formateSessionHTML(session);
                if (sessionsList.querySelector(`.session[data-session-id="${sessionId}"]`)) return;  // Пропускаем уже существующее сообщение в чате

                if (isCurrent) {
                    const deleteActiveSessionsBtn = sessions.length == 1 ? '' : `
                        <button class="sessions-active-delete-btn">
                            <span>Завершить все другие сеансы</span>
                        </button>
                    `;
                    const currentSessionHTML = `
                        <div class="session-current-list">
                            <h3>Этот сеанс<h3>
                            <div class="list">${elementHTML}</div>
                            ${deleteActiveSessionsBtn}
                        </div>
                    `;
                    sessionsList.insertAdjacentHTML('beforeend', currentSessionHTML);
                } else {
                    sessionsHTML += elementHTML;
                }
            });
            const allSessionsHTML = `
                <div class="session-active-list">
                    <h3>Активные сеансы</h3>
                    <div class="list">${sessionsHTML}</div>
                </div>
            `;
            sessionsList.insertAdjacentHTML('beforeend', allSessionsHTML);
        }
        
        const noSessionsClass = 'sessions-error-get-message';
        const hasSessions = sessionsList.children.length > 0 && !sessionsList.querySelector(`.${noSessionsClass}`);
        
        if (!hasSessions) {
            sessionsList.innerHTML = `<h3 class="${noSessionsClass}">Не удалось получить список сеансов. Попробуйте ещё раз.</h3>`;
        } else {
            // Завершить все другие сеансы
            sessionsList.querySelector('.sessions-active-delete-btn')?.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!await confirmationModal('Вы точно хотите завершить все сеансы, кроме текущего?')) return;
                terminateAllOtherSessionsAPI();  // Удаляем сессии
            });

            // Завершить сеанс
            const activeSessionsList = sessionsList.querySelector('.session-active-list .list');
            activeSessionsList.querySelectorAll('.session-delete-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    if (!await confirmationModal('Завершить этот сеанс?')) return;
                    terminateSessionAPI(e.target.closest('.session').dataset.sessionId);  // Удаляем сессию
                });
            });
        }
    }





    // =============== ПАНЕЛИ КАТЕГОРИЙ ===============
    // Переключаем панели
    function changeCategory(category, panel) {
        currentCategory = category;

        // Панели категорий
        sessionsDataPanel.classList.toggle('active', panel === sessionsDataPanel);

        // Кнопки категорий
        sessionsDataCategoryBtn.classList.toggle('active', panel === sessionsDataPanel);
    }





    // =============== API ===============
    
    // Сохранить настройки пользователя
    async function editSettingsDataAPI() {
        setMessageVisible(false);
        
        // const data = {
        //     groupId: groupId,
        //     category: currentCategory
        // };

        // // Подгружаем данные для каждой категории отдельно
        // let successMsg = '';
        // switch (currentCategory) {
        //     case 'base':
        //         successMsg = 'Основная информация была обновлена.';
        //         const baseInfo = {
        //             name: groupName.value,
        //             linkname: groupLinkname.value === '' ? `group${groupId}` : groupLinkname.value
        //         };
        //         data.base = JSON.stringify(baseInfo);
        //         break;
        
        //     default:
        //         break;
        // }

        // try {
        //     const result = await groupsEdit(data);

        //     if (result.success) {
        //         updateMessage(successMsg, 'success');
        //         if (currentCategory === 'base') {
        //             groupLinkname.value = result.linkname;
        //             groupBackBtn.href = result.linkname;
                    
        //             // Устанавливаем новый адрес (без поддержки истории)
        //             const pathParts = window.location.pathname.split('/');
        //             pathParts[pathParts.length - 1] = result.linkname;
        //             const newPath = pathParts.join('/');
        //             const newUrl = newPath + window.location.search + window.location.hash;
        //             window.history.replaceState({}, '', newUrl);
        //         }
        //     } else {
        //         updateMessage(result.error || 'Ошибка соединения. Попробуйте ещё раз.', 'error');
        //     }
            
        // } catch (err) {
        //     updateMessage('Ошибка сервера.', 'error');
        // }
    }

    // Получить список сессий
    async function getSessionsList() {
        let sessions = [];
        try {
            const result = await sessionsGet();

            if (result.success) {
                sessions = result.sessions;
            } else {
                updateMessage(result.error || 'Ошибка соединения. Попробуйте ещё раз.', 'error');
            }
            
        } catch (err) {
            updateMessage('Ошибка сервера.', 'error');
        }
        updateSessionsList(sessions);
    }
    
    // Завершить все другие сеансы
    async function terminateAllOtherSessionsAPI() {
        try {
            const result = await sessionsTerminateAllOther();

            if (result.success) {
                getSessionsList();  // Загружаем список сессий
            } else {
                updateMessage(result.error || 'Ошибка соединения. Попробуйте ещё раз.', 'error');
            }
            
        } catch (err) {
            updateMessage('Ошибка сервера.', 'error');
        }
    }
    
    // Завершить сеанс
    async function terminateSessionAPI(sessionId) {
        try {
            const result = await sessionsTerminate(sessionId);

            if (result.success) {
                getSessionsList();  // Загружаем список сессий
            } else {
                updateMessage(result.error || 'Ошибка соединения. Попробуйте ещё раз.', 'error');
            }
            
        } catch (err) {
            updateMessage('Ошибка сервера.', 'error');
        }
    }





    // =============== КНОПКИ ===============
    // Сохранение данных группы
    document.getElementById('saveData').addEventListener('click', (e) => {
        e.preventDefault();
        editSettingsDataAPI();
    });

    // Категория "Сессии"
    sessionsDataCategoryBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        // Категория сессий
        if (sessionsDataCategoryBtn.classList.contains('active')) return;

        getSessionsList();  // Загружаем список сессий
        categoryTitle.innerHTML = 'Сессии';
        changeCategory('sessions', sessionsDataPanel)
    });




    
    // =============== СТАРТОВАЯ НАСТРОЙКА ===============
    sessionsDataCategoryBtn.click();
});
