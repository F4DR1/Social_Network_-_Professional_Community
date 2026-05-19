<?php
    require_once __DIR__ . '/../bootstrap.php';
    ob_start();
?>

<?php http_response_code(404); ?>
<div class="error-404">
    <div class="error-icon">⚠️</div>
    <h1>404</h1>
    <p>Такой страницы нет</p>
    <a href="/">На главную</a>
</div>

<?php
    $content = ob_get_clean();
    $title = "Такой страницы нет";
    $scripts = [];
    $stylesheets = [
        'pages/404.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Micro;
    require ROOT_PATH . '/layout.php';
?>
