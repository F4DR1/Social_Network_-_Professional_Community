document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const groupId = window.appData.groupId;
    const currentUserId = window.appData.currentUserId;

    
    const mainRequestSubscribe = document.getElementById('mainRequestSubscribe');
    const mainRequestUnsubscribe = document.getElementById('mainRequestUnsubscribe');
    const secondMessage = document.getElementById('secondMessage');

    const membersCount = document.getElementById('membersCount');
    const membersList = document.getElementById('membersList');



    // Переключение видимости элементов
    function switchElements(is_subscribe, is_owner) {
        [mainRequestSubscribe, mainRequestUnsubscribe, secondMessage]
            .forEach(el => el?.classList.remove('active'));

        
        if (is_subscribe) {
            if (!is_owner) {
                mainRequestUnsubscribe.classList.add('active');
            }
            secondMessage.classList.add('active');
        } else {
            mainRequestSubscribe.classList.add('active');
        }
    }

    // Обновление участников группы
    function updateMembersPanel(members) {
        membersCount.textContent = members['count'] || 0;
        
        // Очищаем старый список
        membersList.innerHTML = '';
        
        // Добавляем всех пользователей
        if (members.users && members.users.length > 0) {
            members.users.forEach(user => {
                const userLink = document.createElement('a');
                userLink.href = user.linkname || `user${user.id}`;
                userLink.className = 'member-item';
                
                userLink.innerHTML = `
                    <img src="${user.photo || 'images/empty.webp'}" 
                        alt="${user.firstname}" 
                        width="60" height="60">
                    <p>${user.firstname}</p>
                `;
                
                membersList.appendChild(userLink);
            });
        }
    }
    


    // Получает статус подписки данного пользователя
    async function getSubscribeStatus() {
        const url = "../actions/group/get.php";
        const data = {
            group_id: groupId,
            user_id: currentUserId
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (result.success) {
                switchElements(result.is_subscribe, result.is_owner);
            } else {
                console.log(result.message || "Ошибка соединения");
            }
        } catch (err) {
            console.log("Ошибка сервера");
        }
    }

    // Получает участников группы
    async function getMembers() {
        const url = "../actions/group/get_members.php";
        const data = {
            group_id: groupId
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (result.success) {
                updateMembersPanel(result.members);
            } else {
                console.error(result.error || "Ошибка загрузки участников");
                membersCount.textContent = '0';
                membersList.innerHTML = '<p>Ошибка загрузки участников</p>';
            }
        } catch (err) {
            console.error("Ошибка сервера:", err);
            membersList.innerHTML = '<p>Ошибка соединения</p>';
        }
    }




    // Получаем текущее отношение к группе при загрузке
    getSubscribeStatus();

    // Получаем участников
    getMembers();





    // Отправить данные на скрипт
    async function sendGroupSubscribeData(action, value) {
        const url = "../actions/group/subscribe_update.php";
        const data = {
            group_id: groupId,
            user_id: currentUserId,
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
                getSubscribeStatus();
                getMembers();
            } else {
                console.log(result.message || "Ошибка соединения");
            }
        } catch (err) {
            console.log("Ошибка сервера");
        }
    }



    // Вступление в группу
    document.getElementById("subscribeButton").addEventListener("click", (e) => {
        e.preventDefault();
        sendGroupSubscribeData('Subscribe', true);
    });

    // Выход из группы
    document.getElementById("unsubscribeButton").addEventListener("click", (e) => {
        e.preventDefault();
        sendGroupSubscribeData('Subscribe', false);
    });
});
