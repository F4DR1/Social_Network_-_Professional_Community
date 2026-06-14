// search.js
import {
    searchesSearch
} from '../api.js';

document.addEventListener('DOMContentLoaded', function() {
    if (!window.searchData) {
        console.error('searchData не определен');
        return;
    }
    
    let searchCategory = window.searchData.searchCategory;
    let searchText = window.searchData.searchText;

    if (searchCategory == null) searchCategory = 'users';
    if (searchText == null) searchText = '';




    const searchInput = document.getElementById('searchInput');

    const searchResultList = document.getElementById('searchResultList');





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
                <h3 class="title" data-count="${list.length}">${title}</h3>
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
        // Устанавливаем новый адрес (без поддержки истории)
        const url = new URL(window.location);
        url.searchParams.set('category', searchCategory);
        url.searchParams.set('text', searchText);
        window.history.replaceState({}, '', url);

        const currentCategory = searchCategory;
        try {
            const data = {
                category: currentCategory,
                text: searchText
            }

            const result = await searchesSearch(data);

            if (result.success) {
                updateResultList(currentCategory, result.list);

            } else {
                console.log(result.error || 'Ошибка поиска');
            }

        } catch (err) {
            console.log(err);
        }
    }







    // =============== ОБРАБОТЧИКИ ===============
    // Обработка ввода пользователем
    let typingTimer;
    searchInput?.addEventListener('input', async (e) => {
        // Ищем после того, как пользователь перестал печатать (исключаем спам к API)
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            searchText = searchInput.value.trim();

            // Поиск
            searchAPI();

            typingTimer = null;
        }, 2000);
    });

    // Обработчики для кнопок категорий
    const categoryBtns = document.getElementById('searchButtonsList').querySelectorAll('.category-btn');
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (btn.classList.contains('active')) return;

            const category = btn.dataset.category;
            categoryBtns.forEach(el => {
                el.classList.toggle('active', el.dataset.category == category);
            });
            
            // Поиск
            searchCategory = category;
            searchAPI();
        });
    });



    // Стартовая настройка страницы
    searchInput.value = searchText;
    document.querySelector(`#searchButtonsList .category-btn[data-category="${searchCategory}"]`).click();
});
