<?php
    require_once __DIR__ . '/../bootstrap.php';
    ob_start();
?>

<?php http_response_code(403); ?>
<div class="error-403">
    <div class="error-icon">⚠️</div>
    <h1>403</h1>
    <p>Нет доступа к странице</p>
    <a href="/">На главную</a>
</div>

<?php
    $content = ob_get_clean();
    $title = 'Нет доступа к странице';
    $scripts = [];
    $stylesheets = [
        'pages/403.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Micro;
    require ROOT_PATH . '/layout.php';
?>
