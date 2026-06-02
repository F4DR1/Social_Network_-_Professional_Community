<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;


    $subscribeGroups = [];
    $myGroups = [];
    
    $sections = [
        ['type' => 'myGroups', 'title' => 'Мои группы:'],
        ['type' => 'allGroups', 'title' => 'Мои подписки:']
    ];
    
    ob_start();
?>


<div class="centered-container">
    <section class="container user-groups-list-panel">
        <h2 class="container-title">Группы</h2>

        <!-- Категории контактор -->
        <?php foreach ($sections as $section): ?>
            <div class="category" id="<?= $section['type'] ?>">
                <h3 class="title" data-count=""><?= $section['title'] ?></h3>
                <div class="list"></div>
            </div>
        <?php endforeach ?>

    </section>
</div>

<div class="right-container">
    <!-- Кнопка создания группы -->
    <section class="container group-create-panel">
        <button id="openCreateGroupPanel">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="8"/>
                <line x1="12" y1="9" x2="12" y2="15"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
            </svg>
            <span>Создать группу</span>
        </button>
    </section>
</div>



<script>
    window.appData = <?= json_encode([
        'currentUserId' => $currentUserId
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Группы';
    $scripts = [
        'elements/category_elements.js',
        'pages/groups.js'
    ];
    $stylesheets = [
        'pages/groups.css',
        'elements/category.css',
        'elements/group_card.css',
        'elements/group_create.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
