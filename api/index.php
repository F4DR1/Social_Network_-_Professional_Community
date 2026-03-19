<?php
    // Разрешаем запросы с любого сайта (для разработки)
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // Для preflight запросов OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }


    // error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    // error_log("PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'empty'));
    // error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);

    
    // Подключаем файлы с классами
    require_once 'core/Database.php';
    require_once 'core/Auth.php';
    require_once 'core/Router.php';

    // Создаем подключение к БД
    $db = new Database();

    // Создаем объект для проверки авторизации
    $auth = new Auth($db);

    // Создаем роутер
    $router = new Router();


    // =============== РЕГИСТРИРУЕМ МАРШРУТЫ ===============
    // Какой URL какому контроллеру и методу передать

    // Авторизация (тут auth не нужен, т.к. человек еще не залогинен)
    $router->add('POST', '/logout', 'AuthController', 'logout', $db, null);
    $router->add('POST', '/login', 'AuthController', 'login', $db, null);
    $router->add('POST', '/register', 'AuthController', 'register', $db, null);
    $router->add('POST', '/auth/check', 'AuthController', 'check', $db, null);
    
    // Сессии
    $router->add('GET', '/sessions', 'SessionController', 'getAllMySessions', $db, $auth);
    $router->add('DELETE', '/sessions/current', 'SessionController', 'terminateCurrentSession', $db, $auth);
    $router->add('DELETE', '/sessions/id', 'SessionController', 'terminateSession', $db, $auth);
    $router->add('DELETE', '/sessions', 'SessionController', 'terminateAllOtherSessions', $db, $auth);

    // Отношения
    $router->add('GET', '/relationships/list', 'RelationshipController', 'getList', $db, null);
    $router->add('GET', '/relationships/get/{user_id}/{related_user_id}', 'RelationshipController', 'getRelationship', $db, null);
    $router->add('PUT', '/relationships/subscribe', 'RelationshipController', 'subscribe', $db, $auth);
    $router->add('DELETE', '/relationships/unsubscribe', 'RelationshipController', 'unsubscribe', $db, $auth);
    $router->add('PUT', '/relationships/block', 'RelationshipController', 'block', $db, $auth);
    $router->add('PUT', '/relationships/change-list', 'RelationshipController', 'changeList', $db, $auth);

    // Пользователи
    $router->add('GET', '/users/{id}', 'UserController', 'getUserById', $db, null);
    $router->add('GET', '/users/by-link/{linkname}', 'UserController', 'getUserByLinkname', $db, null);
    $router->add('PUT', '/users/update', 'UserController', 'updateProfile', $db, $auth);

    // Группы
    $router->add('GET', '/groups/{group_id}', 'GroupController', 'getGroupById', $db, null);
    $router->add('GET', '/groups/by-link/{linkname}', 'GroupController', 'getGroupByLinkname', $db, null);
    $router->add('GET', '/groups/list/{user_id}/{is_admin}', 'GroupController', 'getUserGroups', $db, null);
    $router->add('GET', '/groups/is-admin/{group_id}/{user_id}', 'GroupController', 'getUserIsAdminGroup', $db, null);
    $router->add('POST', '/groups/create', 'GroupController', 'createGroup', $db, $auth);
    $router->add('POST', '/groups/edit/{group_id}', 'GroupController', 'editGroup', $db, $auth);

    // // Посты
    // $router->add('POST', '/posts', 'PostController', 'create', $db, $auth);
    // $router->add('GET', '/feed', 'FeedController', 'getFeed', $db, $auth);

    
    // =============== ЗАПУСКАЕМ МАРШРУТИЗАЦИЮ ===============
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
?>