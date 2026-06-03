<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;

    ob_start();
?>



<div class="centered-container">
    <section class="container search-result-panel">
        <h2 class="container-title">Результаты поиска</h2>
        
        <div class="search-result-list" id="searchResultList"></div>

    </section>
</div>


<!-- Боковой контейнер (находится справа) -->
<div class="right-container">
    <section class="container category-navigation-panel">
        <h2 class="container-title">Поиск</h2>

        <div class="input-field">
            <input type="search" id="searchInput">
            <label>Текст для поиска</label>
        </div>
        
        <div class="category-navigation" id="searchButtonsList">
            <button class="category-btn category-users" data-category="users">
                <span>Пользователи</span>
            </button>
            <button class="category-btn category-groups" data-category="groups">
                <span>Группы</span>
            </button>
        </div>

    </section>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Поиск';
    $scripts = [
        'elements/category_elements.js',
        'pages/search.js'
    ];
    $stylesheets = [
        'pages/search.css',
        'elements/category.css',
        'elements/user_card.css',
        'elements/group_card.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
