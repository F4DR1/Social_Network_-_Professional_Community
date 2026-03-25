<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;


    $subscribeGroups = [];
    $myGroups = [];
    
    $sections = [
        ['type' => 'my-groups', 'title' => 'Мои группы:'],
        ['type' => 'all-groups', 'title' => 'Мои подписки:']
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
        <button id="create-group-button">Создать группу</button>
    </div>
</div>


<div class="modal" id="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Создание группы</h2>
        </div>
        <div class="modal-main">
            <div class="input-field">
                <input type="text" id="group-name" name="name" required autocomplete="name">
                <label class="required">Название группы</label>
            </div>
            <p class="message" id="error-message"></p>
        </div>
        <div class="modal-footer">
            <button class="cancel-btn" id="cancel-button">Отмена</button>
            <button class="accept-btn" id="accept-button">Создать группу</button>
        </div>
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
        'category_elements.js',
        'groups.js'
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
