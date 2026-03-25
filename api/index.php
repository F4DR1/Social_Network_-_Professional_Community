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

    // ========== АВТОРИЗАЦИЯ ==========
    $router->add('POST', '/logout', 'AuthController', 'logout', $db, null);
    $router->add('POST', '/login', 'AuthController', 'login', $db, null);
    $router->add('POST', '/register', 'AuthController', 'register', $db, null);
    $router->add('POST', '/auth/check', 'AuthController', 'check', $db, null);
    
    // ========== СЕССИИ ==========
    $router->add('GET', '/sessions', 'SessionController', 'getAllMySessions', $db, $auth);
    $router->add('DELETE', '/sessions/current', 'SessionController', 'terminateCurrentSession', $db, $auth);
    $router->add('DELETE', '/sessions/id', 'SessionController', 'terminateSession', $db, $auth);
    $router->add('DELETE', '/sessions', 'SessionController', 'terminateAllOtherSessions', $db, $auth);

    // ========== ОТНОШЕНИЯ ==========
    $router->add('GET', '/relationships/list', 'RelationshipController', 'getList', $db, null);
    $router->add('GET', '/relationships/get/{user_id}/{related_user_id}', 'RelationshipController', 'getRelationship', $db, null);
    $router->add('PUT', '/relationships/subscribe', 'RelationshipController', 'subscribe', $db, $auth);
    $router->add('DELETE', '/relationships/unsubscribe', 'RelationshipController', 'unsubscribe', $db, $auth);
    $router->add('PUT', '/relationships/block', 'RelationshipController', 'block', $db, $auth);
    $router->add('PUT', '/relationships/change-list', 'RelationshipController', 'changeList', $db, $auth);

    // ========== ПОЛЬЗОВАТЕЛИ ==========
    $router->add('GET', '/users/{id}', 'UserController', 'getUserById', $db, null);
    $router->add('GET', '/users/by-link/{linkname}', 'UserController', 'getUserByLinkname', $db, null);
    $router->add('PUT', '/users/update', 'UserController', 'updateProfile', $db, $auth);

    // ========== ГРУППЫ ==========
    $router->add('GET', '/groups/{group_id}', 'GroupController', 'getGroupById', $db, null);
    $router->add('GET', '/groups/by-link/{linkname}', 'GroupController', 'getGroupByLinkname', $db, null);
    $router->add('GET', '/groups/list/{user_id}/{is_admin}', 'GroupController', 'getUserGroups', $db, null);
    $router->add('GET', '/groups/is-admin/{group_id}/{user_id}', 'GroupController', 'getUserIsAdminGroup', $db, null);
    $router->add('POST', '/groups/create', 'GroupController', 'createGroup', $db, $auth);
    $router->add('POST', '/groups/edit', 'GroupController', 'editGroup', $db, $auth);
    $router->add('GET', '/groups/members/{group_id}', 'GroupController', 'members', $db, null);
    $router->add('GET', '/groups/status/subscribe/{group_id}', 'GroupController', 'statusSubscribe', $db, $auth);
    $router->add('POST', '/groups/subscribe', 'GroupController', 'subscribe', $db, $auth);
    $router->add('POST', '/groups/unsubscribe', 'GroupController', 'unsubscribe', $db, $auth);

    // ========== ПОСТЫ ==========
    $router->add('GET', '/posts/feed', 'PostController', 'getAllPostsFeed', $db, $auth);
    $router->add('GET', '/posts/user/{user_id}', 'PostController', 'getAllPostsByUser', $db, null);
    $router->add('GET', '/posts/group/{group_id}', 'PostController', 'getAllPostsByGroup', $db, null);
    $router->add('GET', '/posts/{post_id}', 'PostController', 'getPost', $db, null);
    $router->add('POST', '/posts/create', 'PostController', 'create', $db, $auth);
    $router->add('POST', '/posts/delete', 'PostController', 'delete', $db, $auth);

    
    // =============== ЗАПУСКАЕМ МАРШРУТИЗАЦИЮ ===============
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
?>