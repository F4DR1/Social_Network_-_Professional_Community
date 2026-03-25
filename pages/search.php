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
        <h2>Поиск</h2>

        <?php foreach ($sections as $section): ?>
            <div class="category" id="<?= $section['type'] ?>">
                <h3 class="title"><?= $section['title'] ?></h3>
                <div class="list"></div>
            </div>
        <?php endforeach ?>

    </div>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Поиск';
    $scripts = [
        'category_elements.js',
        'search.js'
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
