<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;
    
    
    $searchCategory = $_GET['category'] ?? '';
    $searchText = $_GET['text'] ?? '';

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

        
        <!-- Строка поиска -->
        <div class="input-field">
            <span>Текст для поиска:</span>
            <div class="field">
                <div class="input-typed">
                    <span class="included">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </span>
                    <input class="search-text" id="searchInput" type="search" placeholder="Введите текст для поиска...">
                </div>
            </div>
        </div>
        
        <div class="category-navigation" id="searchButtonsList">
            <span>Категории для поиска:</span>
            <button class="category-btn category-users" data-category="users">
                <span>Пользователи</span>
            </button>
            <button class="category-btn category-groups" data-category="groups">
                <span>Группы</span>
            </button>
        </div>

    </section>
</div>


<script>
    window.searchData = <?= json_encode([
        'searchCategory' => $searchCategory ?: null,
        'searchText' => $searchText ?: null
    ]) ?>;
</script>



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
