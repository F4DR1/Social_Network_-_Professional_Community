import {
    updateUserProfile
} from '../api.js?v=20';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    // Базовые данные
    const userPath = window.appData.userPath;
    const userId = window.appData.userId;



    let currentCategory = 'base';


    
    const userBackBtn = document.getElementById('userPath');
    const editUserDataCategoryBtnsPanel = document.getElementById('editUserDataCategoryButtonsPanel');
    const editProfileDataPanel = document.getElementById('editProfileDataPanel');


    // Название категории
    const categoryTitle = editProfileDataPanel.querySelector('.container-title');

    // Панель сообщения
    const messagePanel = editProfileDataPanel.querySelector('.message-panel');
    const messageIcon = messagePanel.querySelector('.message-icon');
    const messageTitle = messagePanel.querySelector('.message-title');
    const messageText = messagePanel.querySelector('.message-text');


    
    // Элементы базовой категории
    const mainDataCategoryBtn = editUserDataCategoryBtnsPanel.querySelector('.category-main')
    const mainDataPanel = editProfileDataPanel.querySelector('.main-data');
    const userLinkname = mainDataPanel.querySelector('.user-linkname');



    
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





    // =============== ПАНЕЛИ КАТЕГОРИЙ ===============
    // Переключаем панели
    function changeCategory(category, panel) {
        currentCategory = category;

        // Панели категорий
        mainDataPanel.classList.toggle('active', panel === mainDataPanel);

        // Кнопки категорий
        mainDataCategoryBtn.classList.toggle('active', panel === mainDataPanel);
    }





    // =============== API ===============
    // Отправить данные на скрипт
    async function editUserDataAPI() {
        setMessageVisible(false);
        
        const data = {
            userId: userId,
            category: currentCategory
        };

        // Подгружаем данные для каждой категории отдельно
        let successMsg = '';
        switch (currentCategory) {
            case 'base':
                successMsg = 'Основная информация была обновлена.';
                const baseInfo = {
                    linkname: userLinkname.value === '' ? `user${userId}` : userLinkname.value
                };
                data.base = JSON.stringify(baseInfo);
                break;
        
            default:
                break;
        }

        try {
            const result = await updateUserProfile(data);

            if (result.success) {
                updateMessage(successMsg, 'success');
                if (currentCategory === 'base') {
                    userLinkname.value = result.linkname;
                    userBackBtn.href = result.linkname;
                    
                    // Устанавливаем новый адрес (без поддержки истории)
                    const pathParts = window.location.pathname.split('/');
                    pathParts[pathParts.length - 1] = result.linkname;
                    const newPath = pathParts.join('/');
                    const newUrl = newPath + window.location.search + window.location.hash;
                    window.history.replaceState({}, '', newUrl);
                }
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
        editUserDataAPI();
    });

    mainDataCategoryBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // Категория основных данных
        if (mainDataCategoryBtn.classList.contains('active')) return;
        categoryTitle.innerHTML = 'Основная информация';
        changeCategory('base', mainDataPanel)
    });




    
    // =============== СТАРТОВАЯ НАСТРОЙКА ===============
    mainDataCategoryBtn.click();
});
