// posts.js
// LEGACY
import {
    postsGetByFeed, postsGetAllByUser, postsGetAllByGroup,
    postPublicate, postDelete,
    contentArticleGet, contentArticleCreate
} from '../api.js';
 
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const postsType = window.appData.postsType;
    const groupId = window.appData.groupId;
    const userId = window.appData.userId;
 
    const currentUserId = window.appData.currentUserId;
 
 
    const postsList = document.getElementById('postsList');
    
    
    // ID элементов модального окна создания нового поста
    const newPostModalId = 'newPostModal';
    const newPostTextId = 'newPostText';
    const publicateNewPostBtnId = 'publicateNewPostButton';
    
    // ID элементов модального окна создания новой статьи
    const newArticleModalId = 'newArticleModal';
    const newArticleTitleId = 'newArticleTitle';
    const newArticleTextId = 'newArticleText';
    const createNewArticleBtnId = 'createNewArticleButton';
    const publicateNewArticleBtnId = 'publicateNewArticleButton';
 



 
    // =============== МОДАЛЬНОЕ ОКНО ЧТЕНИЯ СТАТЬИ ===============
    async function openContentArticle(articleId) {
        const id = `OpenArticleId${articleId}`;
 
        let titleHTML = null;
        let contentHTML = null;
        let footerHTML = null;
        try {
            const result = await contentArticleGet(articleId);
 
            if (result.success) {
                const article = result.article;
                titleHTML = `
                    <img src="${article.coverMediaUrl}" alt="${article.coverMediaTitle}" width="350" height="200">
                    <h2>${article.title}</h2>
                    <p>Время прочтения: <span>${article.readTime} мин.</span></p>
                `
                contentHTML = article.contentHtml;
                footerHTML = `
                    <p>
                        ${article.updatedAt !== article.createdAt ? 'Обновлено' : 'Создано'}:
                        <time class="article-date" datetime="${article.updatedAt}">${relativeTime(article.updatedAt, true)}</time>
                    </p>
                `
            } else {
                console.log(result.error || 'Ошибка обработки контента');
            }
 
        } catch (err) {
            console.error('Ошибка сервера');
        }
 
        
        const modalFooterId = id + 'Footer';
        createModalHTML(id, `
            <div class="modal-title">
                ${titleHTML ?? '<h2>Ошибка получения статьи</h2>'}
            </div>
            <div class="modal-main new-post">
                ${contentHTML ?? '<p>Не удалось получить данные статьи. Попробуйте ещё раз позже.</p>'}
            </div>
            <div class="modal-footer" id="${modalFooterId}">
                ${footerHTML ?? ''}
            </div>
        `);
        showModal(id);
    }
 
    
    // =============== МОДАЛЬНЫЕ ОКНА СОЗДАНИЯ ===============
    function createNewPostPanel(id, publicateBtnId, postTextId) {
        const modalFooterId = id + 'Footer';
        const filesPreviewPanelId = 'filesPreview';
        const modal = createModalHTML(id, `
            <div class="modal-title">
                <h2>Новый пост</h2>
            </div>
            <div class="modal-main new-post">
                <div class="input-field">
                    <textarea min="1" max="2048" type="text" id="${postTextId}" required placeholder="Напишите что-нибудь..."></textarea>
                </div>
            </div>
            <div class="modal-footer" id="${modalFooterId}">
                <div id="${filesPreviewPanelId}"></div>
                <button class="modal-btn" id="${publicateBtnId}">Опубликовать пост</button>
            </div>
        `);
        createUploadFilesPanelHTML(modalFooterId, filesPreviewPanelId);
 
        const publicateBtn = document.getElementById(publicateBtnId);
        const postText = document.getElementById(postTextId);
 
        publicateBtn.classList.toggle('active', false);
        publicateBtn.disabled = true;
        
        postText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 5 + 'px';
 
            const hasText = postText.value.trim().length > 0;
            publicateBtn.classList.toggle('active', hasText);
            publicateBtn.disabled = !hasText;
        });
 
        publicateBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const hasText = postText.value.trim().length > 0;
            const filesIds = window.selectedFileIds;
 
            if (hasText || filesIds) {
                let content;
                if (filesIds) {
                    content = {
                        type: 'files',
                        filesIds: filesIds
                    }
                }
                postPublicateAPI(postText.value, content);
            }
        });
        
        showModal(modal);
    }
    
    function createNewArticlePanel(id, createBtnId, publicateBtnId, titleTextId, articleTextId) {
        const coverMediaInputId = id + 'CoverMedia';
        const filesPreviewPanelId = 'filesPreview';
        const modal = createModalHTML(id, `
            <div class="modal-title">
                <h2>Новая статья</h2>
            </div>
            <div class="modal-main new-post">
                <div class="input-cover-media" id="${coverMediaInputId}">
                    <div id="${filesPreviewPanelId}"></div>
                </div>
                <div class="input-field">
                    <textarea min="1" max="255" type="text" id="${titleTextId}" required placeholder="Введите название статьи..."></textarea>
                </div>
                <div class="input-field">
                    <textarea min="1" max="4096" type="text" id="${articleTextId}" required placeholder="Напишите что-нибудь..."></textarea>
                    <span title="Для создания статьи используется разметка &quot;Markdown&quot;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/>
                            <path d="M9 9C9 7.89543 9.89543 7 11 7H13C14.1046 7 15 7.89543 15 9C15 10.1046 14.1046 11 13 11H12V13M12 17H12.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="secondary-btn modal-btn" id="${createBtnId}">Сохранить статью</button>
                <button class="primary-btn modal-btn" id="${publicateBtnId}">Опубликовать статью</button>
            </div>
        `);
        createUploadImageHTML(coverMediaInputId, filesPreviewPanelId);
 
        const createBtn = document.getElementById(createBtnId);
        const publicateBtn = document.getElementById(publicateBtnId);
        const titleText = document.getElementById(titleTextId);
        const articleText = document.getElementById(articleTextId);
 
        titleText.addEventListener('input', function() {
            textareaResize(this.value, createBtn, publicateBtn);
        });
        articleText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 5 + 'px';
            textareaResize(this.value, createBtn, publicateBtn);
        });
 
        createBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            console.log('Функция сохранения статей временно отключена...');
        });
 
        publicateBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            handleArticleButton(titleText.value, articleText.value, true);
        });
        
        showModal(modal);
    }
 
    function textareaResize(text, createBtn, publicateBtn) {
        const hasText = text.trim().length > 0;
        createBtn.classList.toggle('active', hasText);
        createBtn.disabled = !hasText;
        publicateBtn.classList.toggle('active', hasText);
        publicateBtn.disabled = !hasText;
    }
 
    function handleArticleButton(titleText, articleText, isPublicate = false) {
        const hasText = titleText.trim().length > 0 && articleText.trim().length > 0;
        if (hasText) {
            const coverMediaId = window.selectedFileIds[0] ?? null;
            const data = {
                title: titleText.trim(),
                coverMediaId: coverMediaId,
                content: articleText.trim()
            };
            createContentAPI('article', data, isPublicate);
        }
    }
 
 

    // =============== СОЗДАНИЕ ПОСТА В ЛЕНТЕ ===============
    function createElementPostsList(post) {
        const emptyImage = window.APP_CONFIG.IMAGES + '/empty.webp';
 
        let authorLinkname = post.author_linkname || `user${post.author_id}`;
        let authorName = post.author_name || 'Пользователь';
        let authorPhoto = post.author_photo || emptyImage;
        const userAuthorLinkname = authorLinkname;
        const userAuthorName = authorName;
 
        const isGroupPost = !!post.group_id;
        if (isGroupPost) {
            authorLinkname = post.group_linkname || `group${post.group_id}`;
            authorName = post.group_name || 'Группа';
            authorPhoto = post.group_photo || emptyImage;
        }
 
        const postFiles = post.content.files || null;
        const postArticle = post.content.article || null;
        
        const postText = post.text || '';
        let postContent = '';
        
        if (postFiles != null) {
            let filePreview = '';
            postFiles.forEach(file => {
                filePreview += `
                    <button class="btn post-file-preview" data-file-id="${file.id}">
                        <img src="${file.url}" alt="${file.title}" width="350" height="200">
                    </button>
                `;
            });
            postContent += `
                <div class="post-file">
                    ${filePreview}
                </div>
            `;
        }
        if (postArticle != null) {
            postContent += `
                <div class="post-article">
                    <button class="btn post-article-preview" data-article-id="${postArticle.id}">
                        <div class="article-preview">
                            <img src="${postArticle.coverMediaUrl}" alt="${postArticle.coverMediaTitle || 'Обложка отсутствует'}" width="350" height="200">
                            <h3>${postArticle.title}</h3>
                            <p>Время прочтения: <span>${postArticle.readTime}</span></p>
                        </div>
                        <span class="read-btn-text">Читать</span>
                    </button>
                </div>
            `;
        }
 
        let deleteBtn = '';
        if (currentUserId !== null) {
            deleteBtn = postsType == 'feed' ? '' : `
                <button class="dropdown-button delete-post-btn" data-post-id="${post.id}" type="button">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                    <span>Удалить пост</span>
                </button>
            `;
        }
        const groupPostAuthor = isGroupPost ? `
            <a class="group-post-author" href="${userAuthorLinkname}">
                От ${userAuthorName}
            </a>
        ` : '';
 
        return `
            <article class="post" data-post-id="${post.id}">
                <div class="post-head">
                    <a class="post-author" href="${authorLinkname}">
                        <img src="${authorPhoto}" alt="${authorName}" width="40" height="40">
                        <p>${authorName}</p>
                    </a>
                    <div class="post-actions">
                        <!-- Кнопка-выпадающий список -->
                        <div class="custom-dropdown">
                            <!-- Кнопка-триггер для открытия выпадающего списка -->
                            <button class="dropdown-trigger" type="button">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                    <circle cx="5" cy="12" r="2"/>
                                    <circle cx="12" cy="12" r="2"/>
                                    <circle cx="19" cy="12" r="2"/>
                                </svg>
                            </button>
 
                            <!-- Список действий -->
                            <ul class="dropdown-menu">
                                <li>
                                    ${deleteBtn}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="post-content">
                    <p class="post-text">${postText}</p>
                    ${postContent}
                </div>
 
                <div class="post-footer">
                    ${groupPostAuthor}
                    <time class="post-date" datetime="${post.updated_at}">${relativeTime(post.updated_at, true)}</time>
                </div>
            </article>
        `;
    }
    
    function checkEmptyPostsList() {
        const hasPosts = postsList.children.length > 0 && !postsList.querySelector('.no-posts-message');
        if (!hasPosts) {
            postsList.innerHTML = '<p class="no-posts-message">Постов пока нет</p>';
        }
    }
 
    function updatePostsLists(posts) {
        postsList.innerHTML = '';
        
        if (posts && posts.length > 0) {
            posts.forEach(post => {
                const postHTML = createElementPostsList(post);
                postsList.insertAdjacentHTML('beforeend', postHTML);
            });
            initPostsListActions();
        }
        
        checkEmptyPostsList();
    }
 
    // Инициализация действий постов
    function initPostsListActions() {
        // ── ИСПРАВЛЕНИЕ: переинициализируем .custom-dropdown внутри постов ──
        // Посты вставляются динамически — custom_dropdown.js уже отработал
        // к этому моменту и не видит новые элементы. Поэтому навешиваем
        // логику открытия/закрытия вручную для дропдаунов внутри .post-actions.
        postsList.querySelectorAll('.post-actions .custom-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            if (!trigger) return;
 
            // Убираем старый обработчик (если переинициализируем)
            const newTrigger = trigger.cloneNode(true);
            trigger.parentNode.replaceChild(newTrigger, trigger);
 
            newTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
 
                const isOpen = dropdown.classList.contains('open');
 
                // Закрываем все остальные открытые дропдауны на странице
                document.querySelectorAll('.custom-dropdown.open').forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });
 
                dropdown.classList.toggle('open', !isOpen);
            });
        });
 
        // Закрываем при клике вне
        // (глобальный обработчик уже есть в custom_dropdown.js,
        //  но на случай если нет — добавляем здесь тоже)
        if (!window._postsOutsideClickBound) {
            window._postsOutsideClickBound = true;
            document.addEventListener('click', () => {
                postsList.querySelectorAll('.post-actions .custom-dropdown.open').forEach(d => {
                    d.classList.remove('open');
                });
            });
        }
 
        if (postsType == 'feed') return;
 
        // Удаление постов
        postsList.querySelectorAll('.delete-post-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const post = e.currentTarget.closest('.post');
                const postId = post.dataset.postId;
        
                const confirmed = await confirmationModal('Вы уверены, что хотите удалить этот пост? Это действие не обратимо!', 'Удаление поста');
                if (!confirmed) return;
                deletePostAPI(postId, post);
            });
        });
 
        // Открыть статью для чтения
        postsList.querySelectorAll('.post-article-preview').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const articleId = btn.dataset.articleId;
                openContentArticle(articleId);
            });
        });
    }
 
 

    

    // =============== API ===============
    async function postsGetAPI() {
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
                    result = await postsGetByFeed();
                    break;
                default:
                    console.error('Не удалось определить метод загрузки постов');
                    return;
            }
 
            if (result.success) {
                updatePostsLists(result.posts);
                return;
            } else {
                console.log(result.error || 'Ошибка обработки постов');
            }
 
        } catch (err) {
            console.error('Ошибка сервера');
        }
        updatePostsLists([]);
    }
 
    async function postPublicateAPI(text, content = null) {
        let data = {};
        
        if (content) {
            data.content = content;
        }
        if (text && text.trim()) {
            data.text = text.trim();
        } else if (!content) {
            console.error('Не указан текст поста');
            return;
        }
        if (groupId) {
            data.groupId = groupId;
        }
 
        try {
            const result = await postPublicate(data);
 
            if (result.success) {
                window.selectedFileIds = [];
                location.reload();
            } else {
                console.log(result.error || 'Ошибка обработки постов');
            }
 
        } catch (err) {
            console.error('Ошибка сервера');
        }
        updatePostsLists([]);
    }
 
    async function deletePostAPI(postId, post) {
        const data = { postId: postId };
        if (groupId) {
            data.groupId = groupId;
        }
        
        try {
            const result = await postDelete(data);
 
            if (result.success) {
                post.style.transition = 'all 0.3s ease';
                post.style.opacity = '0';
                post.style.transform = 'translateX(-30px)';
                setTimeout(() => {
                    post.remove();
                    checkEmptyPostsList();
                }, 300);
            } else {
                console.log(result.error || 'Ошибка обработки постов');
            }
 
        } catch (err) {
            console.error('Ошибка сервера');
        }
    }
 
    async function createContentAPI(contentType, data, isPublicate = false) {
        try {
            let result;
            switch (contentType) {
                case 'article':
                    result = await contentArticleCreate(data);
                    break;
                default:
                    throw new Error('Не известный тип контента');
            }
 
            if (result.success) {
                if (isPublicate) {
                    const content = {
                        type: contentType,
                        id: result.newContentId
                    }
                    postPublicateAPI('', content);
                } else {
                    alert('Статья была создана!');
                }
            } else {
                console.log(result.error || 'Ошибка обработки постов');
            }
 
        } catch (err) {
            console.error('Ошибка сервера');
        }
    }
 
 



    // =============== СТАРТОВЫЙ ВЫЗОВ ===============
    postsGetAPI();
 
 



    // =============== ОБРАБОТКА КНОПОК СОЗДАНИЯ ===============
    document.getElementById('newPostButton')?.addEventListener('click', (e) => {
        e.preventDefault();
        createNewPostPanel(newPostModalId, publicateNewPostBtnId, newPostTextId);
    });
    
    document.getElementById('newArticleButton')?.addEventListener('click', (e) => {
        e.preventDefault();
        createNewArticlePanel(newArticleModalId, createNewArticleBtnId, publicateNewArticleBtnId, newArticleTitleId, newArticleTextId);
    });
});