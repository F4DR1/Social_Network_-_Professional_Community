import { postsGetFeed, postsGetAllByUser, postsGetAllByGroup, postsCreate, postsDelete } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const postsType = window.appData.postsType;
    const groupId = window.appData.groupId;
    const userId = window.appData.userId;

    const newPostText = document.getElementById('newPostText');
    const postsList = document.getElementById('postsList');



    // Форматирование даты
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU');
    }

    // Функция создания HTML поста из данных
    function createPostHTML(post) {
        const emptyImage = window.APP_CONFIG.IMAGES + '/empty.webp';

        let authorLinkname = post.author_linkname || `user${post.author_id}`;
        let authorName = post.author_name || 'Пользователь';
        let authorPhoto = post.author_photo || emptyImage;
        let userAuthorLinkname = authorLinkname;
        let userAuthorName = authorName;

        const isGroupPost = !!post.group_id;
        if (isGroupPost) {
            authorLinkname = post.group_linkname || `group${post.group_id}`;
            authorName = post.group_name || 'Группа';
            authorPhoto = post.group_photo || emptyImage;
        }
        
        
        // content парсим из JSON строки
        const content = typeof post.content === 'string' ? JSON.parse(post.content) : post.content;
        const textContent = content.text || '';
        
        const postDate = formatDate(post.created_at);


        const groupPostAuthor = isGroupPost ? `
            <a class="group-post-author" href="${userAuthorLinkname}">
                От ${userAuthorName}
            </a>
        ` : '';
        return `
            <div class="post" data-post-id="${post.id}">
                <div class="post-head">
                    <a class="post-author" href="${authorLinkname}">
                        <img src="${authorPhoto}" alt="${authorName}" width="40" height="40">
                        <p>${authorName}</p>
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
                    ${groupPostAuthor}
                    <p class="post-date">${postDate}</p>
                </div>
            </div>
        `;
    }
    

    // Проверка списка постов на пустоту
    function checkEmptyPosts() {
        const hasPosts = postsList.children.length > 0 && !postsList.querySelector('.no-posts-message');
        
        if (!hasPosts) {
            postsList.innerHTML = '<p class="no-posts-message">Постов пока нет</p>';
        }
    }

    // Обновить посты на странице
    function updatePosts(posts) {
        postsList.innerHTML = '';
        
        if (posts && posts.length > 0) {
            posts.forEach(post => {
                const postHTML = createPostHTML(post);
                postsList.insertAdjacentHTML('beforeend', postHTML);
            });
            initPostActions();
        }
        
        checkEmptyPosts();
    }

    // Инициализация действий постов
    function initPostActions() {
        // Dropdown
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


        const data = {
            postId: postId
        };
        
        if (groupId) {
            data.groupId = groupId;
        }
        
        try {
            const result = await postsDelete(data);

            if (result.success) {
                postElement.style.transition = 'all 0.3s ease';
                postElement.style.opacity = '0';
                postElement.style.transform = 'translateX(-30px)';
                postElement.remove();
                checkEmptyPosts();
            } else {
                console.log(result.error || "Ошибка обработки постов");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
    }
    

    // Очистка полей создания поста
    function clearNewPost() {
        newPostText.value = '';
        newPostText.style.height = 'auto';
    }





    // Получить посты
    async function getPostsAPI() {
        try {
            let result;
            switch (postsType) {
                case 'group':
                    result = await postsGetAllByGroup(groupId);
                    break;
                    
                case 'user':
                    result = await postsGetAllByUser(userId);
                    break;
                    
                case 'feed':
                    result = await postsGetFeed();
                    break;
            
                default:
                    console.error('Не удалось определить метод загрузки постов');
                    return;
            }

            if (result.success) {
                updatePosts(result.posts);
                return;
            } else {
                console.log(result.error || "Ошибка обработки постов");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updatePosts([]);
    }

    // Отправить пост
    async function sendPostAPI() {
        const data = {
            content: JSON.stringify({
                text: newPostText.value.trim()
            })
        };
        
        if (groupId) {
            data.groupId = groupId;
        }
        
        try {
            const result = await postsCreate(data);

            if (result.success) {
                clearNewPost();
                getPostsAPI();
            } else {
                console.log(result.error || "Ошибка обработки постов");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updatePosts([]);
    }


    
    // Загружаем посты
    getPostsAPI();



    // Создать пост
    document.getElementById("postNewPost").addEventListener("click", (e) => {
        e.preventDefault();
        sendPostAPI();
    });

    // Авторесайз textarea
    newPostText.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
