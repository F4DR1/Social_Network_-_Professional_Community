<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';

    function httpErrorCheck($httpCode) {
        switch ($httpCode) {
            case 404:
                include ERROR_PAGES_PATH . '/404.php';
                break;

            case 403:
                include ERROR_PAGES_PATH . '/403.php';
                break;
            
            default:
                break;
        }
        exit;
    }
?>
