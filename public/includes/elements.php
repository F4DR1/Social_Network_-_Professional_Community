<?php
    /**
     * Возвращает поле создания поста
     */
    function postCreationField() {
        return <<<HTML
            <section class="container">
                <button class="post-create-btn" id="newPostButton">Создать пост</button>
                <button class="article-create-btn" id="newArticleButton">📝</button>
            </section>
        HTML;
    }
    /**
     * Возвращает поле для постов
     */
    function postsPanel($title = null) {
        $title = htmlspecialchars($title ?? 'Посты');
        return <<<HTML
            <section class="container">
                <h2>$title</h2>
                <div class="posts" id="postsList"></div>
            </section>
        HTML;
    }
?>
