<?php
    require_once __DIR__ . '/bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/http_errors.php';

    global $currentUser;

    // Получаем путь из параметра route (либо из REQUEST_URI, если используем прямую передачу)
    $route = $_GET['route'] ?? '';
    $route = trim($route, '/');
    unset($_GET['route']); // чтобы не мешался в дальнейшем
    

    // Разбираем route на сегменты (например, 'feed' или 'user123')
    $segments = $route ? explode('/', $route) : [];
    
    
    // ===== МАРШРУТЫ ОШИБОК =====
    $errorPages = ['403', '404'];
    if (in_array($route, $errorPages)) {
        httpErrorCheck($route);
    }

    // ===== СТАТИЧНЫЕ МАРШРУТЫ =====
    $staticPages = ['feed', 'msg', 'contacts', 'groups', 'search', 'settings', 'login', 'register', 'recovery'];
    if (in_array($route, $staticPages)) {
        switch ($route) {
            case 'feed':
                include PAGES_PATH . '/feed.php';
                break;
            case 'msg':
                include PAGES_PATH . '/messenger.php';
                break;
            case 'contacts':
                include PAGES_PATH . '/contacts.php';
                break;
            case 'groups':
                include PAGES_PATH . '/groups.php';
                break;
            case 'search':
                include PAGES_PATH . '/search.php';
                break;
            case 'settings':
                include PAGES_PATH . '/settings.php';
                break;
            case 'login':
            case 'register':
            case 'recovery':
                if (!empty($currentUser)) {
                    header('Location: ' . BASE_URL . '/feed');
                    exit;
                }
                
                $form = $route;  // Передаём активную форму
                $_GET['form'] = $form;
                include PAGES_PATH . '/auth.php';
                break;
            default:
                include PAGES_PATH . '/' . $route . '.php';
                break;
        }
        exit;
    }

    // ==== ПРОФИЛИ ПО ID (user123, group456) ====
    if (preg_match('/^(user|group)(\d+)$/', $route, $matches)) {
        $type = $matches[1];
        $id = (int)$matches[2];
        
        if ($type === 'user') {
            $result = usersGetId($id);
            if ($result['success']) {
                gotoUserProfile($result['data']['user']);
            } else {
                httpErrorCheck($result['http_code']);
            }
        } else {
            $result = groupsGetId($id);
            if ($result['success']) {
                gotoGroupProfile($result['data']['group']);
            } else {
                httpErrorCheck($result['http_code']);
            }
        }
        exit;
    }

    // ==== ПРОФИЛИ ПО LINKNAME (username, groupname) ====
    // Разрешаем буквы, цифры, дефис и подчёркивание
    if ($route !== '' && preg_match('/^[a-zA-Z0-9_-]+$/', $route)) {
        // Сначала ищем пользователя
        $result = usersGetLinkname($route);
        if ($result['success']) {
            gotoUserProfile($result['data']['user']);
            exit;
        }

        // Если не пользователь, ищем группу
        $result = groupsGetLinkname($route);
        if ($result['success']) {
            gotoGroupProfile($result['data']['group']);
            exit;
        }

        // Ничего не найдено – 404
        httpErrorCheck(404);
        exit;
    }

    // ==== ГЛАВНАЯ СТРАНИЦА (пустой route) ====
    if ($route === '') {
        if (!empty($currentUser)) {
            header('Location: ' . BASE_URL . '/feed');
        } else {
            // По умолчанию показываем форму входа
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        exit;
    }

    // Всё, что не подошло – 404
    httpErrorCheck(404);
    exit;



    // // Старый код индекса
    // // Проверяем параметры URL
    // $linkname = $_GET['linkname'] ?? '';
    // $type = $_GET['type'] ?? '';
    // $id = $_GET['id'] ?? '';

    // // Поиск по linkname (site.ru/linkname)
    // if (!empty($linkname)) {
    //     // Пользователи
    //     $result = usersGetLinkname($linkname);
    //     if ($result['success']) {
    //         gotoUserProfile($result['data']['user']);
    //         exit;
    //     }

    //     // Группы
    //     $result = groupsGetLinkname($linkname);
    //     if ($result['success']) {
    //         gotoGroupProfile($result['data']['group']);
    //         exit;
    //     }

    //     httpErrorCheck(404);
    //     exit;
    // }

    // // Поиск по ID (site.ru/user123, site.ru/group456)
    // if (!empty($type) && !empty($id) && is_numeric($id)) {
    //     switch ($type) {
    //         // Пользователи
    //         case 'user':
    //             $result = usersGetId($id);
    //             if ($result['success']) {
    //                 gotoUserProfile($result['data']['user']);
    //             } else {
    //                 httpErrorCheck($result['http_code']);
    //             }
    //             break;
                
    //         // Группы
    //         case 'group':
    //             $result = groupsGetId($id);
    //             if ($result['success']) {
    //                 gotoGroupProfile($result['data']['group']);
    //             } else {
    //                 httpErrorCheck($result['http_code']);
    //             }
    //             break;
            
    //         default:
    //             httpErrorCheck(404);
    //             break;
    //     }
    //     exit;
    // }

    // // Остальные случаи
    // if (!empty($currentUser)) {
    //     include PAGES_PATH . '/feed.php';
    //     exit;
        
    // } else {
    //     include PAGES_PATH . '/auth.php';
    //     exit;
    // }




    
    function gotoUserProfile($user) {
        $_GET['user'] = $user;
        include PAGES_PATH . '/user_profile.php';
        exit;
    }

    function gotoGroupProfile($group) {
        $_GET['group'] = $group;
        include PAGES_PATH . '/group_profile.php';
        exit;
    }
?>
