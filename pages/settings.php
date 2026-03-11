<?php
    require_once '../includes/init.php';
    global $db_frontend, $current_user_id;
    
    // if (empty($GLOBALS['current_user_id'])) {
    if (!isset($current_user_id)) {
        header('Location: /');
        exit;
    }
    
    ob_start();
?>

<div class="centered-container">
    <div class="container">
        <h2>Мои настройки</h2>
        <p>Здесь вы можете настроить свой профиль.</p>
    </div>
    
    <div class="container">
        <h3>Основная информация</h3>
        <p>Настройки основной информации профиля.</p>
    </div>
    
    <div class="container">
        <h3>Безопасность</h3>
        <p>Настройки безопасности аккаунта.</p>
    </div>
</div>

<div class="right-container">
    <div class="container">
        <h3>Быстрые действия</h3>
        <p>Быстрый доступ к часто используемым функциям.</p>
    </div>
    
    <div class="container">
        <h3>Статистика</h3>
        <p>Ваша активность за последнее время.</p>
    </div>
</div>

<?php
    $content = ob_get_clean();
    $title = 'Мои настройки';
    $scripts = [];
    $stylesheets = [];
    require_once '../enums/layout.php';
    $layout = Layout::Standart;
    require '../layout.php';
?>