<?php
    // index.php
    require_once __DIR__ . '/bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/http_errors.php';

    global $currentUser;

    // Получаем путь из параметра route (либо из REQUEST_URI, если используем прямую передачу)
    $route = $_GET['route'] ?? '';
    $route = ltrim($route, '/');
    unset($_GET['route']);


    // Перенаправляем URL с конечным слешем на версию без слеша (301)
    if ($route !== '' && str_ends_with($route, '/')) {
        $cleanRoute = rtrim($route, '/');
        $params = $_GET;
        unset($params['route']);
        $queryString = http_build_query($params);
        $newUrl = BASE_URL . '/' . $cleanRoute;
        if ($queryString) {
            $newUrl .= '?' . $queryString;
        }
        header('Location: ' . $newUrl, true, 301);
        exit;
    }


    // Адрес для перенаправления
    $returnUrl = $_GET['return_url'] ?? BASE_URL;
    

    // // Разбираем route на сегменты (например, 'feed' или 'user123')
    // $segments = $route ? explode('/', $route) : [];


    
    // ===== МАРШРУТЫ ОШИБОК =====
    $errorPages = ['403', '404'];
    if (in_array($route, $errorPages)) {
        httpErrorCheck($route);
    }

    // ===== СТАТИЧНЫЕ МАРШРУТЫ =====
    $authStaticPages = ['login', 'register', 'recovery'];
    $allStaticPages = ['feed', 'msg', 'contacts', 'groups', 'search', 'settings', 'about'];
    if (empty($currentUser)) {
        // Доступны только адреса авторизации
        if (in_array($route, $authStaticPages)) {
            $_GET['form'] = $route;
            $_GET['return_url'] = $returnUrl;
            include PAGES_PATH . '/auth.php';
            exit;
        } else {
            if (in_array($route, $allStaticPages)) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

    } else {
        // Доступны все адреса, кроме адресов авторизации
        if (in_array($route, $allStaticPages)) {
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
                case 'about':
                    include PAGES_PATH . '/about.php';
                    break;
                default:
                    include PAGES_PATH . '/' . $route . '.php';
                    break;
            }
            exit;
        }
        if (in_array($route, $authStaticPages)) {
            header('Location: ' . BASE_URL . '/feed');
            exit;
        }
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
    
    



    // Открыть страницу пользователя
    function gotoUserProfile($user) {
        $_GET['user'] = $user;
        include PAGES_PATH . '/user_profile.php';
        exit;
    }

    // Открыть страницу группы
    function gotoGroupProfile($group) {
        $_GET['group'] = $group;
        include PAGES_PATH . '/group_profile.php';
        exit;
    }
?>
