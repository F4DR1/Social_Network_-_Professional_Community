<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $current_user_id;
    
    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Группы</h2>

        <div class="groups" id="groupsList">

        </div>
    </div>
</div>

<div class="right-container">
    <div class="container">
        <button id="createGroup">Создать группу</button>
    </div>
</div>

<div class="modal" id="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Создание группы</h2>
        </div>
        <div class="modal-main">
            <div class="input-field">
                <input type="text" id="groupName" name="name" required autocomplete="name">
                <label>Название группы*</label>
            </div>
            <p class="message" id="errorMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="cancel-btn" id="cancelButton">Отмена</button>
            <button class="accept-btn" id="acceptButton">Создать группу</button>
        </div>
    </div>
</div>



<script>
    window.appData = <?= json_encode([
        'currentUserId' => $current_user_id
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Группы';
    $scripts = [
        'groups.js'
    ];
    $stylesheets = [
        'groups.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
