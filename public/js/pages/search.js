// import { search } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    const usersCategory = document.getElementById('searched-users');
    const groupsCategory = document.getElementById('searched-groups');



    // Обновить посты в категории на странице
    function updateCategory(array, categoryType) {
        if (!array || !categoryType || !usersCategory || !groupsCategory) return;

        let category;
        switch (categoryType) {
            case 'users':
                category = usersCategory;
                break;
                
            case 'groups':
                category = groupsCategory;
                break;
        
            default:
                return;
        }

        const list = category.getElementsByClassName('list')[0];
        list.innerHTML = '';
        if (array.length > 0) {
            array.forEach(el => {
                let elHTML;
                switch (categoryType) {
                    case 'users':
                        elHTML = createUserHTML(el);
                        break;
                        
                    case 'groups':
                        elHTML = createGroupHTML(el);
                        break;
                
                    default:
                        return;
                }
                list.insertAdjacentHTML('beforeend', elHTML);
            });
        } else {
            list.innerHTML = `<p>Пусто.</p>`;
        }
    }


    
    // Получить список пользователей
    async function getUsersList() {
        try {
            const result = await search();

            if (result.success) {
                updateCategory(result.users, 'users');
            } else {
                console.error(result.error || "Ошибка получения списка пользователей");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updateCategory([], 'users');
    }

    // Получить список групп
    async function getGroupsList() {
        try {
            const result = await search();

            if (result.success) {
                updateCategory(result.groups, 'groups');
            } else {
                console.error(result.error || "Ошибка получения списка групп");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updateCategory([], 'groups');
    }



    // Получить списки
    getUsersList();
    getGroupsList();
});
