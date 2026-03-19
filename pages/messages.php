<?php
    require_once '../includes/init.php';
    global $db_frontend, $current_user_id;


    
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';

    
    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Сообщения</h2>


        <div class="chats" id="chats">
            <!-- Здесь все чаты -->
        </div>
        <div class="messages" id="messages">
            <!-- Здесь все сообщения чата (отображается только когда выбран чат) -->
            <?php if (!empty($type) && !empty($id)): ?>
                <p>Тут в будущем будет чат <?= $type === 'user' ? "с пользователем <u>user$id</u>" : "группы <u>group$id</u>" ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Сообщения';
    $scripts = [
        'messages.js'
    ];
    $stylesheets = [];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
