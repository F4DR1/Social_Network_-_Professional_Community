// user_profile.js
import {
    relationshipsSubscribe, relationshipsUnsubscribe
} from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const currentPanel = window.appData.panel;

    const profilePath = window.appData.path;

    const currentUserId = window.appData.currentUserId;
    const userId = window.appData.userId;
    



    
    //=============== API ===============
    // Изменить подписку
    async function subscribeAPI(isSubscribe) {
        const data = {
            related_user_id: userId
        };

        try {
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





    // Кнопки взаимодействия с пользователем
    if (userId != currentUserId) {
        // =============== КНОПКИ В ЧУЖОМ ПРОФИЛЕ ===============
        document.getElementById('mainMessageUserButton')?.addEventListener('click', (e) => {
            // Написать пользователю
            e.preventDefault();
            window.location.href = `${window.APP_CONFIG.BASE_URL}/msg?type=user&id=${userId}`;
        });
        document.getElementById('baseMessageUserButton')?.addEventListener('click', (e) => {
            // Написать пользователю
            e.preventDefault();
            window.location.href = `${window.APP_CONFIG.BASE_URL}/msg?type=user&id=${userId}`;
        });
        document.getElementById('followButton')?.addEventListener('click', (e) => {
            // Отправить заявку
            e.preventDefault();
            subscribeAPI(true);
        });
        document.getElementById('unfollowButton')?.addEventListener('click', (e) => {
            // Отменить заявку
            e.preventDefault();
            subscribeAPI(false);
        });
        document.getElementById('acceptButton')?.addEventListener('click', (e) => {
            // Принять заявку
            e.preventDefault();
            subscribeAPI(true);
        });
        document.getElementById('deleteButton')?.addEventListener('click', (e) => {
            // Удалить из контактов
            e.preventDefault();
            subscribeAPI(false);
        });

    }



    

    // =============== КНОПКИ МЕНЮ В ЛЮБОМ ПРОФИЛЕ ===============
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
