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
    function switchElements(is_following_record, following_is_blocked, is_follower_record, follower_is_blocked) {
        // console.log("Переключаем...");
        const followingIsBlocked = following_is_blocked;
        const followerIsBlocked = follower_is_blocked;

        const mutual =
            (is_following_record && !followingIsBlocked) &&  // Пользователь подписан
            (is_follower_record && !followerIsBlocked);  // Пользователь подписан
        const onlyFollowing =
            (is_following_record && !followingIsBlocked) &&  // Пользователь подписан
            (!is_follower_record || followerIsBlocked);  // Пользователь не подписан или заблокировал пользователя
        const onlyFollower =
            (!is_following_record || followingIsBlocked) &&  // Пользователь не подписан или заблокировал пользователя
            (is_follower_record && !followerIsBlocked);  // Пользователь подписан
        const none = !mutual && !onlyFollowing && !onlyFollower ? true : false;  // Остальные случаи

        // console.log(mutual);
        // console.log(onlyFollowing);
        // console.log(onlyFollower);
        // console.log(none);

        [mainMessage, mainRequest, mainRequestFollowing, mainRequestFollower, baseActionMessage, contactAction]
            .forEach(el => el?.classList.remove('active'));

        if (mutual) {
            mainMessage.classList.add('active');
            contactAction.classList.add('active');
        } else if (onlyFollowing) {
            mainRequestFollowing.classList.add('active');
            baseActionMessage.classList.add('active');
        } else if (onlyFollower) {
            mainRequestFollower.classList.add('active');
            baseActionMessage.classList.add('active');
        } else if (none) {
            mainRequest.classList.add('active');
            baseActionMessage.classList.add('active');
        }
    }


    // Получает статус отношений с данным пользователем
    async function getStatus() {
        if (currentUserId === userId) return;

        const url = "../actions/relationship/get.php";
        const data = {
            current_user_id: currentUserId,
            user_id: userId,
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (result.success) {
                // console.log(result.is_following_record);
                // console.log(result.following_is_blocked);
                // console.log(result.is_follower_record);
                // console.log(result.follower_is_blocked);
                switchElements(result.is_following_record, result.following_is_blocked, result.is_follower_record, result.follower_is_blocked);
            } else {
                console.log(result.message || "Ошибка соединения");
            }
        } catch (err) {
            console.log("Ошибка сервера");
        }
    }




    // Получаем текущие взаимоотношения при загрузке
    getStatus();





    // Отправить данные на скрипт
    async function sendRelationshipData(action, value) {
        const url = "../actions/relationship/update.php";
        const data = {
            user_id: currentUserId,
            related_user_id: userId,
            action: action,
            value: value
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (result.success) {
                getStatus();
            } else {
                console.log(result.message || "Ошибка соединения");
            }
        } catch (err) {
            console.log("Ошибка сервера");
        }
    }



    // Отправить заявку
    document.getElementById("followButton").addEventListener("click", (e) => {
        e.preventDefault();
        sendRelationshipData('Follow', true);
    });

    // Отменить заявку
    document.getElementById("unfollowButton").addEventListener("click", (e) => {
        e.preventDefault();
        sendRelationshipData('Follow', false);
    });

    // Принять заявку
    document.getElementById("acceptButton").addEventListener("click", (e) => {
        e.preventDefault();
        sendRelationshipData('Follow', true);
    });

    // Удалить из контактов
    document.getElementById("deleteButton").addEventListener("click", (e) => {
        e.preventDefault();
        sendRelationshipData('Follow', false);
    });
});
