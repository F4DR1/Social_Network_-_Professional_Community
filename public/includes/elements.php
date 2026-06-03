<?php
    // elements.php
    
    /**
     * Возвращает поле создания поста
     */
    function postCreationField() {
        return <<<HTML
            <!-- Панель создания постов -->
            <section class="container post-create-panel">
                <button class="post-create-btn" id="newPostButton">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <span>Создать пост</span>
                </button>
                <button class="article-create-btn" id="newArticleButton">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                        <polyline points="15,2 15,7 20,7"/>
                        <line x1="12" y1="11" x2="12" y2="17"/>
                        <line x1="9" y1="14" x2="15" y2="14"/>
                    </svg>
                </button>
            </section>
        HTML;
    }
    /**
     * Возвращает поле для постов
     */
    function postsPanel($title = null) {
        $title = htmlspecialchars($title ?? 'Посты');
        return <<<HTML
            <!-- Панель постов -->
            <section class="container posts-panel">
                <h2>$title</h2>
                <div class="posts" id="postsList"></div>
            </section>
        HTML;
    }
?>
