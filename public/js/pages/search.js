// search.js
import {
    searchesSearch
} from '../api.js';

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');

    const searchResultList = document.getElementById('searchResultList');



    let currentCategory = 'users';





    // =============== СПИСОК НАЙДЕННЫХ ЭЛЕМЕНТОВ ===============
    // Обновляем список с результатом поиска
    function updateResultList(category, list) {
        searchResultList.innerHTML = '';

        // Выводим список
        let title = '';
        let elList = '';
        if (list && list.length > 0) {
            switch (category) {
                case 'users':
                    title = 'Пользователи';
                    list.forEach(user => {
                        elList += createUserHTML(user);  // Берётся из category_elements.js
                    });
                    break;

                case 'groups':
                    title = 'Группы';
                    list.forEach(group => {
                        elList += createGroupHTML(group);  // Берётся из category_elements.js
                    });
                    break;
            
                default:
                    break;
            }
        }
        const insertHTML = `
            <div class="category ${category}">
                <h3 class="title">${title}</h3>
                <div class="list">${elList}</div>
            </div>
        `;
        searchResultList.insertAdjacentHTML('beforeend', insertHTML);
        const categoryList = searchResultList.querySelector('.list');

        // Ничего не найдено
        const hasElements = categoryList.children.length > 0 && !categoryList.querySelector('.no-posts-message');
        if (!hasElements) {
            searchResultList.innerHTML = '<p class="no-elements-message">Не удалось ничего найти.</p>';
        }
    };

    
    


    // =============== API ===============
    // Поиск
    async function searchAPI() {
        const category = currentCategory;
        const data = {
            category: category,
            text: searchInput.value.trim()
        }
        
        try {
            const result = await searchesSearch(data);

            if (result.success) {
                updateResultList(category, result.list);

            } else {
                console.log(result.error || 'Ошибка поиска');
            }

        } catch (err) {
            console.log(err);
        }
    }





    searchAPI();





    // =============== ОБРАБОТЧИКИ ===============
    // Обработка ввода пользователем
    let typingTimer;
    searchInput?.addEventListener('input', async (e) => {
        // Ищем после того, как пользователь перестал печатать (исключаем спам к API)
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            // Поиск
            searchAPI();

            typingTimer = null;
        }, 2000);
    });

    // Обработчики для кнопок категорий
    document.getElementById('searchButtonsList').querySelectorAll('.search-category-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            currentCategory = btn.dataset.category;
            // Переключаем поиск на категорию
            searchAPI();
        });
    });
});
