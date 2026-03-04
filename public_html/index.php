<?php
    require_once 'includes/init.php';
    

    // Проверяем параметры URL
    $linkname = $_GET['linkname'] ?? '';
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';
    
    // 1. Поиск по linkname (site.ru/linkname)
    if (!empty($linkname)) {
        // Ищем пользователя
        $stmt = $db->prepare('SELECT * FROM users WHERE linkname = ?');
        $stmt->bindValue(1, $linkname);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user) {
            gotoUserProfile($user);
            exit;
        }


        // Ищем группу
        $stmt = $db->prepare('SELECT * FROM groups WHERE linkname = ?');
        $stmt->bindValue(1, $linkname);
        $result = $stmt->execute();
        $group = $result->fetchArray(SQLITE3_ASSOC);
        
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
        if ($type === 'user') {
            // Ищем пользователя по ID
            $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($user) {
                gotoUserProfile($user);
                exit;
            }
        } elseif ($type === 'group') {
            // Ищем группу по ID
            $stmt = $db->prepare('SELECT * FROM groups WHERE id = ?');
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $group = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($group) {
                gotoGroupProfile($group);
                exit;
            }
        }

        // Error 404
        include 'pages/404.php';
        exit;
    }

    
    $user_id = isset($_SESSION['user_id']);

    if (!empty($user_id)) {
        // Главная страница
        header('Location: feed');
        exit;
    }
    else {
        // Авторизация
        $form = $_GET['form'] ?? '';
        $return_url = $_GET['return_url'] ?? '/';

        require_once 'enums/auth.php';
        $is_register = $form === Auth::Register->text() ? true : false;
        
        include 'pages/auth.php';
        exit;
    }









    function gotoUserProfile($user) {
        global $db;
        include 'pages/userProfile.php';
        exit;
    }

    function gotoGroupProfile($group) {
        global $db;
        include 'pages/groupProfile.php';
        exit;
    }
?>
