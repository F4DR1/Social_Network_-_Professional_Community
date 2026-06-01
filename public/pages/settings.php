<?php
    // settings.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/page_path.php';
    global $currentUserId;
    
    if (!isset($currentUserId)) {
        header('Location: /');
        exit;
    }
    
    ob_start();
?>



<!-- <div class="centered-container">
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
</div> -->


<div class="centered-container" id="editSettingsDataPanel">
    <section class="container data">
        <h2 class="container-title"></h2>

        <!-- Панель сообщения -->
        <div class="message-panel">
            <svg class="message-icon" viewBox="0 0 24 24" width="50" height="50" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

            </svg>
            <div class="message-main">
                <h3 class="message-title"></h3>
                <p class="message-text"></p>
            </div>
        </div>

        <!-- Данные сессий пользователя -->
        <div class="sessions-data">
            <!-- Список сессий -->
            <div class="sessions-list"></div>
        </div>

        <!-- Кнопка "Сохранить" -->
        <button class="standart-btn" id="saveData">Сохранить</button>

    </section>
</div>

<div class="right-container">
    <!-- Панель кнопок смены категории -->
    <section class="container category-navigation-panel">
        <h2>Категории настроек</h2>
        
        <div class="category-navigation" id="editSettingsDataCategoryButtonsPanel">
            <button class="category-btn category-sessions">
                <span>Сессии</span>
            </button>
        </div>
    </section>
</div>
        

        
<script>
    window.appData = <?= json_encode([
        'userId' => $currentUserId
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Мои настройки';
    $scripts = [
        'elements/custom_dropdown.js',
        'pages/settings.js'
    ];
    $stylesheets = [
        'elements/custom_dropdown.css',
        'pages/settings.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>