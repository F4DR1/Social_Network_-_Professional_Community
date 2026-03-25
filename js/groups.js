import { groupsListGet, groupsCreate } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = window.appData.currentUserId;

    const myGroupsCategory = document.getElementById('my-groups');
    const allGroupsCategory = document.getElementById('all-groups');

    const message = document.getElementById('error-message');



    // Обновить посты в категории на странице
    function updateCategoryGroups(groups, categoryType) {
        if (!groups || !categoryType || !myGroupsCategory || !allGroupsCategory) return;

        let category;
        let emptyText;
        switch (categoryType) {
            case 'my':
                category = myGroupsCategory;
                emptyText = 'У вас нет ни одной группы.';
                break;
                
            case 'all':
                category = allGroupsCategory;
                emptyText = 'Вы не состоите ни в одной группе.';
                break;
        
            default:
                return;
        }
        
        category.getElementsByClassName('title')[0].dataset.count = groups.length > 0 ? groups.length : 0;

        const list = category.getElementsByClassName('list')[0];
        list.innerHTML = '';
        if (groups.length > 0) {
            groups.forEach(group => {
                const groupHTML = createGroupHTML(group);
                list.insertAdjacentHTML('beforeend', groupHTML);
            });
        } else {
            list.innerHTML = `<p>${emptyText}</p>`;
        }
    }



    // Показать сообщение
    function setMessage(text) {
        message.textContent = text;
        message.classList.add('active');
    }

    // Скрыть сообщение
    function clearMessage() {
        message.textContent = '';
        message.classList.remove('active');
    }


    
    // Получить список групп
    async function getGroupsList() {
        try {
            const result = await groupsListGet(currentUserId, false);

            if (result.success) {
                updateCategoryGroups([], 'my');
                updateCategoryGroups(result.groupsList, 'all');
                return;
            } else {
                console.error(result.error || "Ошибка получения списка групп");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updateCategoryGroups([], 'my');
        updateCategoryGroups([], 'all');
    }

    // Создать группу
    async function createGroupAPI() {
        const data = {
            name: document.getElementById("group-name").value,
        };

        try {
            const result = await groupsCreate(data);

            if (result.success) {
                clearMessage();
                setTimeout(() => (window.location.href = `group${result.groupId}`), 2000);

            } else {
                setMessage(result.error || "Ошибка создания группы");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
    }



    // Получить список групп пользователя
    getGroupsList();



    // Начать создание
    document.getElementById('create-group-button').addEventListener("click", showModal);

    // Отменить создание
    document.getElementById('cancel-button').addEventListener("click", hideModal);

    // Создание
    document.getElementById('accept-button').addEventListener("click", createGroupAPI);
});
