<?php
    /**
     * Возвращает поле создания поста
     */
    function postCreationField() {
        return <<<HTML
            <div class="container">
                <h2>Новый пост</h2>
                <div class="new-post">
                    <div class="input-field">
                        <textarea min="1" max="500" type="text" id="newPostText" required placeholder="Напишите что-нибудь..."></textarea>
                    </div>
                    <button id="postNewPost">Опубликовать</button>
                </div>
            </div>
        HTML;
    }
    /**
     * Возвращает поле для постов
     */
    function postsPanel($title = null) {
        $title = htmlspecialchars($title ?? 'Посты');
        return <<<HTML
            <div class="container">
                <h2>$title</h2>
                <div class="posts" id="postsList"></div>
            </div>
        HTML;
    }
?>
