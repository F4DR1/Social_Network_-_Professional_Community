// import { usersRelationshipsGet } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = window.appData.currentUserId;

    const mutualCategory = document.getElementById('mutual-relationships');
    const outgoingCategory = document.getElementById('outgoing-relationships');
    const incomingCategory = document.getElementById('incoming-relationships');



    // Обновить посты в категории на странице
    function updateCategoryUsers(users, categoryType) {
        if (!users || !categoryType || !mutualCategory || !outgoingCategory || !incomingCategory) return;

        let category;
        let emptyText;
        switch (categoryType) {
            case 'mutual':
                category = mutualCategory;
                emptyText = 'У вас нет контактов.'
                break;
                
            case 'outgoing':
                category = outgoingCategory;
                emptyText = 'Вы ни на кого не подписаны.'
                break;
                
            case 'incoming':
                category = incomingCategory;
                emptyText = 'На вас никто не подписан.'
                break;
        
            default:
                return;
        }
        
        category.getElementsByClassName('title')[0].dataset.count = users.length > 0 ? users.length : 0;

        const list = category.getElementsByClassName('list')[0];
        list.innerHTML = '';
        if (users.length > 0) {
            users.forEach(user => {
                const userHTML = createUserHTML(user);
                list.insertAdjacentHTML('beforeend', userHTML);
            });
        } else {
            list.innerHTML = `<p>${emptyText}</p>`;
        }
    }


    
    // Получить список пользователей
    async function getUsersList() {
        try {
            const result = await usersRelationshipsGet(currentUserId);

            if (result.success) {
                updateCategoryUsers(result.userRelationships['mutuals'], 'mutual');
                updateCategoryUsers(result.userRelationships['outgoing'], 'outgoing');
                updateCategoryUsers(result.userRelationships['incoming'], 'incoming');
            } else {
                console.error(result.error || "Ошибка получения списка пользователей");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updateCategoryUsers([], 'mutual');
        updateCategoryUsers([], 'outgoing');
        updateCategoryUsers([], 'incoming');
    }



    // Получить список пользователей
    getUsersList();
});
