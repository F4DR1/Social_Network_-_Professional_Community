import { relationshipsSubscribe, relationshipsUnsubscribe } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const currentUserId = window.appData.currentUserId;
    const userId = window.appData.userId;

    
    
    // Изменить подписку
    async function subscribeAPI(is_subscribe) {
        const data = {
            related_user_id: userId
        };

        try {
            const result = await (is_subscribe ? relationshipsSubscribe(data) : relationshipsUnsubscribe(data));

            if (result.success) {
                location.reload();
            } else {
                console.log(result.error || "Ошибка обработки отношений");
            }

        } catch (err) {
            console.log("Ошибка сервера");
        }
    }



    // Получаем текущие взаимоотношения при загрузке
    if (currentUserId != userId) {
        // Отправить заявку
        try {
            document.getElementById("followButton").addEventListener("click", (e) => {
                e.preventDefault();
                subscribeAPI(true);
            });
        } catch (error) {}

        // Отменить заявку
        try {
            document.getElementById("unfollowButton").addEventListener("click", (e) => {
                e.preventDefault();
                subscribeAPI(false);
            });
        } catch (error) {}

        // Принять заявку
        try {
            document.getElementById("acceptButton").addEventListener("click", (e) => {
                e.preventDefault();
                subscribeAPI(true);
            });
        } catch (error) {}

        // Удалить из контактов
        try {
            document.getElementById("deleteButton").addEventListener("click", (e) => {
                e.preventDefault();
                subscribeAPI(false);
            });
        } catch (error) {}
    }
});
