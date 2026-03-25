<?php
    require_once __DIR__ . '/../bootstrap.php';

    
    /**
     * Формирует базовый URL API в зависимости от окружения
     */
    function getApiUrl($domain) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

        if ($host === 'localhost' || $host === '127.0.0.1') {
            $base = rtrim(BASE_URL, '/');
            $base = ltrim($base, '/');
            return $protocol . '://localhost/' . $base . '/api';
        }
        return $protocol . '://api.' . $domain;
    }

    
    $DOMAIN = 'sitename.com';
    $API = getApiUrl($DOMAIN);

    define('API', $API);
    define('DOMAIN', $DOMAIN);

    // Настройки, которые доступны в JavaScript
    global $clientConfig;
    $clientConfig = [
        'API' => $API,
        'DOMAIN' => $DOMAIN,
        'BASE_URL' => defined('BASE_URL') ? BASE_URL : '/',
        'IMAGES' => IMAGES_URL,
    ];
?>
