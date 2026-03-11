document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const groupId = window.appData.groupId;
    const currentUserId = window.appData.currentUserId;

    const newPostText = document.getElementById('newPostText');

    const postsList = document.getElementById('postsList');




    // Функция создания HTML поста из данных
    function createPostHTML(post) {
        const textContent = post.content.text || '';
        const postDate = formatDate(post.created_at);

        return `
            <div class="post" data-post-id="${post.id}">
                <div class="post-head">
                    <a class="post-author" href="${post.group.linkname || 'group' + post.group.id}">
                        <img src="${post.group.photo || 'images/empty.webp'}" alt="${post.group.name}" width="40" height="40">
                        <p>${post.group.name}</p>
                    </a>
                    <div class="post-actions">
                        <div class="action-dropdown">
                            <button class="standart-btn action-trigger" type="button">
                                <!-- SVG Три точки -->
                                <span>...</span>
                            </button>
                            <ul class="dropdown-list">
                                <li>
                                    <button class="dropdown-button delete-post-btn" data-post-id="${post.id}" type="button">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                        </svg>
                                        <span>Удалить пост</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="post-content">
                    <p class="text-content">${textContent}</p>
                </div>

                <div class="post-footer">
                    <a class="group-post-author" href="${post.author.linkname || 'user' + post.author.id}">
                        От ${post.author.firstname + ' ' + post.author.lastname}
                    </a>
                    <p class="post-date">${postDate}</p>
                </div>
            </div>
        `;
    }
    
    // Форматирование даты
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU');  // 01.01.2000
    }

    // Обновить посты на странице
    function updatePosts(posts) {
        postsList.innerHTML = '';
        
        if (posts && posts.length > 0) {
            posts.forEach(post => {
                const postHTML = createPostHTML(post);
                postsList.insertAdjacentHTML('beforeend', postHTML);
            });
            initPostActions();  // Инициализируем действия для новых постов
        } else {
            postsList.innerHTML = '<p>Постов пока нет</p>';
        }
    }

    // Инициализация действий постов (dropdown + удаление)
    function initPostActions() {
        // Dropdown для мобильных
        document.querySelectorAll('.action-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('.action-trigger');
            trigger?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (window.innerWidth <= 768) {
                    document.querySelectorAll('.action-dropdown').forEach(d => d.classList.remove('active'));
                    dropdown.classList.toggle('active');
                }
            });
        });

        // Удаление постов
        document.querySelectorAll('.delete-post-btn').forEach(btn => {
            btn.addEventListener('click', handleDeletePost);
        });
    }

    // Удаление поста
    async function handleDeletePost(e) {
        e.stopPropagation();
        const postId = e.currentTarget.dataset.postId;
        const postElement = e.currentTarget.closest('.post');
        
        if (!confirm('Удалить этот пост навсегда?')) return;


        const url = "../actions/posts/delete.php";
        const data = {
            post_id: postId,
            group_id: groupId,
            user_id: currentUserId
        };
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            });
            
            const result = await response.json();
            if (result.success) {
                postElement.style.transition = 'all 0.3s ease';
                postElement.style.opacity = '0';
                postElement.style.transform = 'translateX(-30px)';
                setTimeout(() => postElement.remove(), 300);
            } else {
                alert('Ошибка удаления: ' + (result.error || 'Неизвестная ошибка'));
            }
        } catch (err) {
            console.error('Ошибка удаления:', err);
            alert('Ошибка сервера');
        }
    }
    

    // Очистка полей создания поста
    function clearNewPost() {
        newPostText.value = '';
        newPostText.style.height = 'auto';
    }





    // Получить посты
    async function getPostsData() {
        const url = "../actions/posts/get.php";
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
                updatePosts(result.posts);
            } else {
                console.log(result.message || "Ошибка соединения");
                console.error(result.error);
            }
        } catch (err) {
            console.error("Ошибка сервера: " + err);
        }
    }

    // Отправить пост
    async function sendPostData() {
        const url = "../actions/posts/post.php";
        const data = {
            group_id: groupId,
            user_id: currentUserId,
            content: JSON.stringify({
                text: newPostText.value.trim()
                // images: [] // для будущего
            })
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (result.success) {
                clearNewPost();
                getPostsData();
            } else {
                console.log('Пост не был отправлен...');
                console.log(result.message || "Ошибка соединения");
            }
        } catch (err) {
            console.error("Ошибка сервера");
        }
    }

    

    // Инициализация
    clearNewPost();  // Очищаем поля нового поста
    getPostsData(); // Загружаем посты при старте



    // Вступление в группу
    document.getElementById("postNewPost").addEventListener("click", (e) => {
        e.preventDefault();
        sendPostData();
    });

    // Авторесайз textarea
    newPostText.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
