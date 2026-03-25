import { groupsMembers, groupsSubscribe, groupsUnsubscribe } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const groupId = window.appData.groupId;

    
    const membersCount = document.getElementById('membersCount');
    const membersList = document.getElementById('membersList');



    // Обновление участников группы в панели
    function updateMembersPanel(members) {
        if (membersCount) membersCount.textContent = members.length || 0;
        if (!members || !membersList) return;

        membersList.innerHTML = '';

        members.forEach(user => {
            const linkname = user.linkname ?? "user" + user.id;
            const name = user.firstname;
            const photo = user.photo ?? `${window.APP_CONFIG.IMAGES}/empty.webp`;

            const link = document.createElement('a');
            link.href = linkname;
            link.className = 'member-item';
            
            link.innerHTML = `
                <img src="${photo}" alt="${name}" width="60" height="60" loading="lazy">
                <p>${name}</p>
            `;
            
            membersList.appendChild(link);
        });
    }
    


    // Получает участников группы
    async function getMembersAPI() {
        try {
            const result = await groupsMembers(groupId);

            if (result.success) {
                updateMembersPanel(result.members);
            } else {
                console.error(result.error || "Ошибка загрузки участников");
                membersCount.textContent = '0';
                membersList.innerHTML = '<p>Ошибка загрузки участников</p>';
            }
            
        } catch (err) {
            membersList.innerHTML = '<p>Ошибка соединения</p>';
            console.log("Ошибка сервера: " . result.error || '');
        }
    }

    // Отправляет запрос со статусом подписки
    async function sendSubscribeAPI(isSubscribe) {
        const data = {
            groupId: groupId
        };

        try {
            console.log()
            const result = await (isSubscribe ? groupsSubscribe(data) : groupsUnsubscribe(data));

            if (result.success) {
                location.reload();
            } else {
                console.error(result.error || "Ошибка соединения");
            }
            
        } catch (err) {
            console.log("Ошибка сервера: " . result.error || '');
        }
    }



    // Получаем участников
    getMembersAPI();



    // Вступление в группу
    try {
        document.getElementById("subscribeButton").addEventListener("click", (e) => {
            e.preventDefault();
            sendSubscribeAPI(true);
        });
    } catch (error) {}
    

    // Выход из группы
    try {
        document.getElementById("unsubscribeButton").addEventListener("click", (e) => {
            e.preventDefault();
            sendSubscribeAPI(false);
        });
    } catch (error) {}
});
