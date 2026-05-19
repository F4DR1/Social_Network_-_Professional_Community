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
    <div class="container">
        <h2>Группы</h2>

        <?php foreach ($sections as $section): ?>
            <div class="category" id="<?= $section['type'] ?>">
                <h3 class="title" data-count=""><?= $section['title'] ?></h3>
                <div class="list"></div>
            </div>
        <?php endforeach ?>

        </div>
    </div>
</div>

<div class="right-container">
    <div class="container">
        <button id="openCreateGroupPanel">Создать группу</button>
    </div>
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
        'elements/category.css',
        'elements/group_card.css',
        'elements/group_create.css',
        'elements/input_field.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
