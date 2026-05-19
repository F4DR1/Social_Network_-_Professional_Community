<?php
    define('ROOT_PATH', __DIR__);

    // Определяем базовый URL для веб-ресурсов
    if (php_sapi_name() !== 'cli' && isset($_SERVER['SCRIPT_NAME'])) {
        // Берем dirname только от корневого SCRIPT_NAME, игнорируя поддиректории
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        // Если в поддиректории pages, обрезаем её
        if (basename($script_dir) === 'pages') {
            $base_url = dirname($script_dir);
        } else {
            $base_url = $script_dir;
        }
        define('BASE_URL', rtrim($base_url, '/\\\\'));
    } else {
        define('BASE_URL', '');
    }

    // Константы для файловой системы (для PHP-инклюдов)
    define('INCLUDES_PATH', ROOT_PATH . '/includes');
    define('CONFIG_PATH', ROOT_PATH . '/config');
    define('PAGES_PATH', ROOT_PATH . '/pages');
    define('ENUMS_PATH', ROOT_PATH . '/enums');

    // Константы для URL (для вывода в браузер)
    define('JS_URL', BASE_URL . '/js');
    define('CSS_URL', BASE_URL . '/css');
    define('IMAGES_URL', BASE_URL . '/images');

    define('LAYOUT', ROOT_PATH . '/layout.php');
?>
