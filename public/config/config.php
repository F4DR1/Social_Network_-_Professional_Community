<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/page_path.php';

    
    /**
     * Формирует базовый URL API в зависимости от окружения
     */
    function getApiUrl($mainDomain) {
        if (HOST === 'localhost' || HOST === '127.0.0.1') {
            $base = rtrim(BASE_URL, '/');
            $base = ltrim($base, '/');
            if (basename($base) === 'public') {
                $base = dirname($base, 1);
                $base = $base === '.' ? '' : $base . '/';
            }
            return PROTOCOL . '://localhost/' . $base . 'api';
        }
        return PROTOCOL . '://api.' . $mainDomain;
    }


    
    // Автоматически получаем основной домен (без поддомена api.)
    error_log('host: ' . HOST);
    $host = preg_replace('/:\d+$/', '', HOST);  // Удаляем порт
    $DOMAIN = preg_replace('/^api\./', '', $host);  // Убираем 'api.' (если есть, на всякий случай)

    $API = getApiUrl($DOMAIN);

    define('API', $API);
    define('DOMAIN', $DOMAIN);
    error_log('domain: ' . DOMAIN);
    

    // Настройки, которые доступны в JavaScript
    global $clientConfig;
    $clientConfig = [
        'API' => $API,
        'DOMAIN' => $DOMAIN,
        'BASE_URL' => defined('BASE_URL') ? BASE_URL : '/',
        'IMAGES' => IMAGES_URL,
    ];
?>
