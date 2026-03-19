import { groupsListGet, groupsCreate } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = window.appData.currentUserId;

    const groupsList = document.getElementById('groupsList');

    const message = document.getElementById('errorMessage');
    const modal = document.getElementById('modal');


    // Функция создания HTML карточки группы
    function createGroupHTML(group) {

        return `
            <div class="group-panel">
                <img src="${group['photo']}/${window.APP_CONFIG.IMAGES}/empty.webp" alt="<?= htmlspecialchars($group['name']) ?>" width=80>
                <a href="${group['linkname'] ?? 'group' + $group['id']}" class="name-line">${group['name']}</a>
                <a href="messages?type=group&id=${group['id']}" class="message-line">Написать в чат группы</a>
            </div>
        `

        // return `
        //     <div class="post" data-post-id="${post.id}">
        //         <div class="post-head">
        //             <a class="post-author" href="${post.group.linkname || 'group' + post.group.id}">
        //                 <img src="${post.group.photo || 'images/empty.webp'}" alt="${post.group.name}" width="40" height="40">
        //                 <p>${post.group.name}</p>
        //             </a>
        //             <div class="post-actions">
        //                 <div class="action-dropdown">
        //                     <button class="standart-btn action-trigger" type="button">
        //                         <!-- SVG Три точки -->
        //                         <span>...</span>
        //                     </button>
        //                     <ul class="dropdown-list">
        //                         <li>
        //                             <button class="dropdown-button delete-post-btn" data-post-id="${post.id}" type="button">
        //                                 <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
        //                                     <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        //                                 </svg>
        //                                 <span>Удалить пост</span>
        //                             </button>
        //                         </li>
        //                     </ul>
        //                 </div>
        //             </div>
        //         </div>
                
        //         <div class="post-content">
        //             <p class="text-content">${textContent}</p>
        //         </div>

        //         <div class="post-footer">
        //             <a class="group-post-author" href="${post.author.linkname || 'user' + post.author.id}">
        //                 От ${post.author.firstname + ' ' + post.author.lastname}
        //             </a>
        //             <p class="post-date">${postDate}</p>
        //         </div>
        //     </div>
        // `;
    }

    // Обновить посты на странице
    function updateGroups(groups) {
        groupsList.innerHTML = '';
        
        if (groups && groups.length > 0) {
            posts.forEach(group => {
                const groupHTML = createGroupHTML(group);
                groupsList.insertAdjacentHTML('beforeend', groupHTML);
            });
        } else {
            groupsList.innerHTML = '<p>Вы не подписаны ни на одну группу</p>';
        }
    }



    // Показать модальное окно
    function showModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';  // Блокируем скролл
    }

    // Скрыть модальное окно
    function hideModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';  // Возвращаем скролл
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
                await updateGroups(result.groupsList);

            } else {
                console.error(result.error || "Ошибка получения списка групп");
            }

        } catch (err) {
            // console.error("Ошибка сервера");
            setMessage(err.error);
        }
    }

    // Создать группу
    async function createGroupAPI() {
        const data = {
            name: document.getElementById("groupName").value,
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
            // console.error("Ошибка сервера");
            setMessage(err.error);
        }
    }



    // Получить список групп пользователя
    getGroupsList();


    // Начать создание
    document.getElementById('createGroup').addEventListener("click", (e) => {
        e.preventDefault();
        showModal();
    });

    // Отменить создание
    document.getElementById('cancelButton').addEventListener("click", (e) => {
        e.preventDefault();
        hideModal();
    });

    // Создание
    document.getElementById('acceptButton').addEventListener("click", (e) => {
        e.preventDefault();
        createGroupAPI();
    });

    // Закрытие по клику на фон (опционально)
    document.querySelector('.modal').addEventListener('click', function(e) {
        if (e.target === this) hideModal();
    });
});
