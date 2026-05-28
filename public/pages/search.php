<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;
    
    $sections = [
        ['type' => 'searched-users', 'title' => 'Пользователи:'],
        ['type' => 'searched-groups', 'title' => 'Группы:']
    ];

    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Результаты поиска</h2>
        
        <div class="search-result-list" id="searchResultList"></div>

    </div>
</div>


<!-- Боковой контейнер (находится справа) -->
<div class="right-container">
    <div class="container">
        <h2>Поиск</h2>

        <div class="input-field">
            <input type="search" id="searchInput">
            <label>Текст для поиска</label>
        </div>
        
        <div class="search-category-buttons-list" id="searchButtonsList">
            <button class="button search-category-btn" data-category="users">
                <span>Пользователи</span>
            </button>
            <button class="button search-category-btn" data-category="groups">
                <span>Группы</span>
            </button>
        </div>

    </div>
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
