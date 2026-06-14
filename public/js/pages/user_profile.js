// user_profile.js
import {
    relationshipsSubscribe, relationshipsUnsubscribe, relationshipsBlock,
    relationshipsUserList, relationshipsChangeList
} from '../api.js?v=1';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const currentPanel = window.appData.panel;

    const profilePath = window.appData.path;

    const currentUserId = window.appData.currentUserId;
    const userId = window.appData.userId;

    const currentIsBlock = window.appData.currentIsBlock;



    const contactListElement = document.getElementById('contactList');





    // -------------------- РАЗМЕТКА СПИСКОВ КОНТАКТОВ --------------------
    // Формирует разметку сообщения
    function formateContactListHTML(list) {
        const title = list.title;
        const isSelected = list.is_selected;


        const listId = list.id;
        const elementHTML = `
            <button class="contact-list-btn" data-list-id="${listId}" data-is-selected="${!!isSelected}">
                <input class="is-selected visual-status-active" type="checkbox" disabled ${isSelected ? 'checked' : ''}>
                <span>${title}</span>
            </button>
        `
        return {listId, elementHTML};
    }

    // Обновляем список списков контактов
    function updateContactList(lists) {
        contactListElement.innerHTML = '';
        lists.forEach(list => {
            const {listId, elementHTML} = formateContactListHTML(list);
            contactListElement.insertAdjacentHTML('beforeend', elementHTML);

            // Обработчик клика
            const btn = contactListElement.querySelector(`.contact-list-btn[data-list-id="${listId}"]`)
            btn.addEventListener('click', async (e) => {
                if (await changeContactListAPI(listId)) getContactListAPI();
            });
        });

        
        // Сообщение, если нет списков
        const noListsClass = 'no-contact-lists';
        const hasLists = contactListElement.children.length > 0 && !contactListElement.querySelector(`.${noListsClass}`);
        if (!hasLists) {
            contactListElement.innerHTML = `<span class="${noListsClass}">Не удалось получить списки контактов.</span>`;
        }
    }
    



    
    //=============== API ===============
    // Изменить подписку
    async function subscribeToUserAPI(isSubscribe) {
        if (userId === currentUserId) return;
        try {
            const data = {
                related_user_id: userId
            };

            const result = await (isSubscribe ? relationshipsSubscribe(data) : relationshipsUnsubscribe(data));

            if (result.success) {
                location.reload();
            } else {
                console.log(result.error || 'Ошибка обработки отношений');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }
    
    // Заблокировать пользователя
    async function blockUserAPI(isBlocked) {
        if (userId === currentUserId) return;
        try {
            const data = {
                related_user_id: userId,
                is_blocked: isBlocked
            };

            const result = await relationshipsBlock(data);

            if (result.success) {
                location.reload();
            } else {
                console.log(result.error || 'Ошибка обработки отношений');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }
    
    // Получить списки для контакта
    async function getContactListAPI() {
        if (userId === currentUserId) return;
        let list = [];
        try {
            const result = await relationshipsUserList(userId);

            if (result.success) {
                updateContactList(result.list);
            } else {
                console.log(result.error || 'Ошибка получения списков контактов');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }
    
    // Добавить в список
    async function changeContactListAPI(listId) {
        if (userId === currentUserId) return false;
        try {
            const data = {
                related_user_id: userId,
                list_id: listId
            };

            const result = await relationshipsChangeList(data);

            if (result.success) {
                return true;
            } else {
                console.log(result.error || 'Ошибка обработки отношений');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
        return false;
    }





    // =============== КНОПКИ В ПРОФИЛЕ ===============
    if (userId != currentUserId) {
        // ===== ВЗАИМОДЕЙСТВИЯ С ПОЛЬЗОВАТЕЛЕМ =====
        // Отправить заявку
        document.getElementById('followButton')?.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (currentIsBlock) {
                const confirm = await window.confirmationModal(
                    'Пользователь находится в вашем чёрном списке. Вы хотите разблокировать его и отправить заявку в контакты?',
                    'Заявка в контакты заблокированному пользователю'
                );
                if (!confirm) return;
            }

            subscribeToUserAPI(true);
        });
        // Отменить заявку
        document.getElementById('unfollowButton')?.addEventListener('click', async (e) => {
            e.preventDefault();
            subscribeToUserAPI(false);
        });
        // Принять заявку
        document.getElementById('acceptButton')?.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (currentIsBlock) {
                const confirm = await window.confirmationModal(
                    'Пользователь находится в вашем чёрном списке. Вы хотите разблокировать его и принять заявку в контакты?',
                    'Заявка в контакты от заблокированного пользователя'
                );
                if (!confirm) return;
            }

            subscribeToUserAPI(true);
        });
        // Удалить из контактов
        document.getElementById('deleteButton')?.addEventListener('click', async (e) => {
            e.preventDefault();
            subscribeToUserAPI(false);
        });
        

        // Добавить в чёрный список
        document.getElementById('addBlackListButton')?.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (currentIsBlock) {
                window.imformationModal(
                    'Пользователь уже находится в вашем чёрном списке.',
                    'Ошибка добавления пользователя в чёрный список'
                );
                return;
            }
            const confirm = await window.confirmationModal(
                'Вы уверены, что хотите добавить пользователя в чёрный список?',
                'Добавление пользователя в чёрный список'
            );
            if (confirm) blockUserAPI(true);
        });
        // Удалить из чёрного списка
        document.getElementById('removeBlackListButton')?.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (!currentIsBlock) {
                window.imformationModal(
                    'Пользователь не находится в вашем чёрном списке.',
                    'Ошибка удаление пользователя из чёрного списка'
                );
                return;
            }
            const confirm = await window.confirmationModal(
                'Вы уверены, что хотите убрать пользователя из чёрного списка?',
                'Удаление пользователя из чёрного списка'
            );
            if (confirm) blockUserAPI(false);
        });
    }




    getContactListAPI();


    

    // =============== КНОПКИ НАВИГАЦИИ ПАНЕЛЕЙ В ЛЮБОМ ПРОФИЛЕ ===============
    const postsNavBtn = document.getElementById('postsNavigationButton');
    const skillsNavBtn = document.getElementById('skillsNavigationButton');


    postsNavBtn.classList.toggle('selected', (currentPanel == 'posts' || currentPanel == ''));
    skillsNavBtn.classList.toggle('selected', currentPanel == 'skills');
    


    // =============== ОБРАБОТЧИКИ КНОПОК ===============
    postsNavBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = `${profilePath}`;
    });
    skillsNavBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = `${profilePath}?p=skills`;
    });
});
