<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';

    function httpErrorCheck($httpCode) {
        switch ($httpCode) {
            case 404:
                include PAGES_PATH . '/404.php';
                break;
            
            default:
                break;
        }
        exit;
    }
?>
