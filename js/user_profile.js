import { relationshipsGet, relationshipsSubscribe, relationshipsUnsubscribe } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const currentUserId = window.appData.currentUserId;
    const userId = window.appData.userId;

    
    const mainMessage = document.getElementById('main-message');
    const mainRequest = document.getElementById('main-request');
    const mainRequestFollowing = document.getElementById('main-request-following');
    const mainRequestFollower = document.getElementById('main-request-follower');

    const baseActionMessage = document.getElementById('base-action-message');

    const contactAction = document.getElementById('contact-action');



    // Переключение видимости элементов
    function switchElements(data) {
        if (currentUserId == userId) return;
        
        [mainMessage, mainRequest, mainRequestFollowing, mainRequestFollower, baseActionMessage, contactAction]
            .forEach(el => el?.classList.remove('active'));
        
        const isFollower = (data['follower']['is_exists'] && !data['follower']['is_blocked']);
        const isFollowing = (data['following']['is_exists'] && !data['following']['is_blocked']);

        if (isFollower && isFollowing) {
            mainMessage.classList.add('active');
            contactAction.classList.add('active');
        } else if (isFollowing) {
            mainRequestFollowing.classList.add('active');
            baseActionMessage.classList.add('active');
        } else if (isFollower) {
            mainRequestFollower.classList.add('active');
            baseActionMessage.classList.add('active');
        } else {
            mainRequest.classList.add('active');
            baseActionMessage.classList.add('active');
        }
    }


    
    // Получить статус отношений
    async function getStatusAPI(currentUserId, relatedUserId) {
        try {
            const followingResult = await relationshipsGet(currentUserId, relatedUserId);
            const followerResult = await relationshipsGet(relatedUserId, currentUserId);

            const followingIsExists = !(followingResult.relationship == null);
            const followerIsExists = !(followerResult.relationship == null);
            const data = {
                following: {
                    is_exists: followingIsExists,
                    is_blocked: followingIsExists ? followingResult.relationship['is_blocked'] : false
                },
                follower: {
                    is_exists: followerIsExists,
                    is_blocked: followerIsExists ? followerResult.relationship['is_blocked'] : false
                }
            };

            switchElements(data);

        } catch (err) {
            console.log("Ошибка сервера");
        }
    }
    
    // Изменить подписку
    async function subscribeAPI(is_subscribe) {
        const data = {
            related_user_id: userId
        };

        try {
            const result = await (is_subscribe ? relationshipsSubscribe(data) : relationshipsUnsubscribe(data));

            if (result.success) {
                await getStatusAPI(currentUserId, userId);
            } else {
                console.log(result.error || "Ошибка обработки отношений");
            }

        } catch (err) {
            console.log("Ошибка сервера");
        }
    }



    // Получаем текущие взаимоотношения при загрузке
    if (currentUserId != userId) {
        getStatusAPI(currentUserId, userId);
        

        // Отправить заявку
        document.getElementById("followButton").addEventListener("click", (e) => {
            e.preventDefault();
            subscribeAPI(true);
        });

        // Отменить заявку
        document.getElementById("unfollowButton").addEventListener("click", (e) => {
            e.preventDefault();
            subscribeAPI(false);
        });

        // Принять заявку
        document.getElementById("acceptButton").addEventListener("click", (e) => {
            e.preventDefault();
            subscribeAPI(true);
        });

        // Удалить из контактов
        document.getElementById("deleteButton").addEventListener("click", (e) => {
            e.preventDefault();
            subscribeAPI(false);
        });
    }
});
