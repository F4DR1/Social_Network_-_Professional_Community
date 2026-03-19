<?php
    require_once __DIR__ . '/bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/http_errors.php';
    global $current_user_id;

    // Проверяем параметры URL
    $linkname = $_GET['linkname'] ?? '';
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';

    // Поиск по linkname (site.ru/linkname)
    if (!empty($linkname)) {
        // Пользователи
        $result = usersGetLinkname($linkname);
        if ($result['success']) {
            gotoUserProfile($result['data']['user']);
            exit;
        } else {
            httpErrorCheck($result['http_code']);
            exit;
        }

        // Группы
        $result = groupsGetLinkname($linkname);
        if ($result['success']) {
            gotoGroupProfile($result['data']['group']);
            exit;
        } else{
            httpErrorCheck($result['http_code']);
            exit;
        }
    }

    // Поиск по ID (site.ru/user123, site.ru/group456)
    if (!empty($type) && !empty($id) && is_numeric($id)) {
        switch ($type) {
            case 'user':
                $result = usersGetId($id);
                if ($result['success']) {
                    gotoUserProfile($result['data']['user']);
                } else {
                    httpErrorCheck($result['http_code']);
                }
                break;
                
            case 'group':
                $result = groupsGetId($id);
                if ($result['success']) {
                    gotoGroupProfile($result['data']['group']);
                } else {
                    httpErrorCheck($result['http_code']);
                }
                break;
            
            default:
                httpErrorCheck(404);
                break;
        }
        exit;
    }

    // Остальные случаи
    if (!empty($current_user_id)) {
        include PAGES_PATH . '/feed.php';
        exit;
        
    } else {
        include PAGES_PATH . '/auth.php';
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
