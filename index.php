<?php
    require_once 'includes/init.php';
    global $db_frontend, $current_user_id;

    // Проверяем параметры URL
    $linkname = $_GET['linkname'] ?? '';
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';

    // 1. Поиск по linkname (site.ru/linkname)
    if (!empty($linkname)) {
        // Ищем пользователя
        $user = $db_frontend->fetchOne('SELECT * FROM users WHERE linkname = ?', [$linkname]);
        
        if ($user) {
            gotoUserProfile($user);
            exit;
        }

        // Ищем группу  
        $group = $db_frontend->fetchOne('SELECT * FROM groups WHERE linkname = ?', [$linkname]);
        
        if ($group) {
            gotoGroupProfile($group);
            exit;
        }

        // Error 404
        include 'pages/404.php';
        exit;
    }

    // 2. Поиск по ID (site.ru/user123, site.ru/group456)
    if (!empty($type) && !empty($id) && is_numeric($id)) {
        global $db_frontend;
        
        if ($type === 'user') {
            $user = $db_frontend->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
            
            if ($user) {
                gotoUserProfile($user);
                exit;
            }
        } elseif ($type === 'group') {
            $group = $db_frontend->fetchOne('SELECT * FROM groups WHERE id = ?', [$id]);
            
            if ($group) {
                gotoGroupProfile($group);
                exit;
            }
        }

        include 'pages/404.php';
        exit;
    }

    if (!empty($current_user_id)) {
        header('Location: feed');
        exit;
    } else {
        // Авторизация
        $form = $_GET['form'] ?? '';
        $return_url = $_GET['return_url'] ?? '/social_network';

        require_once 'enums/auth.php';
        $is_register = $form === Auth::Register->text() ? true : false;
        
        include 'pages/auth.php';
        exit;
    }




    
    function gotoUserProfile($user) {
        $_GET['user'] = $user;
        include 'pages/userProfile.php';
        exit;
    }

    function gotoGroupProfile($group) {
        $_GET['group'] = $group;
        include 'pages/groupProfile.php';
        exit;
    }
?>
