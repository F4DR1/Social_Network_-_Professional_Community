<?php
    require __DIR__ . '/vendor/autoload.php';
    
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
    
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);  // Ошибки будут писаться в лог, но не в браузер
    

    require_once 'core/Helpers.php';


    // Разрешаем запросы только с конкретного домена
    $allowedOrigin = 'https://' . Helpers::getMainDomain();
    if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowedOrigin) {
        header("Access-Control-Allow-Origin: " . $allowedOrigin);
    } else {
        // Разрешаем запросы с любого сайта (для разработки)
        header("Access-Control-Allow-Origin: *");
    }
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
    $router->add('POST', '/register-validate', 'AuthController', 'registerValidate', $db, null);
    $router->add('POST', '/register', 'AuthController', 'register', $db, null);
    $router->add('POST', '/auth/check', 'AuthController', 'check', $db, null);
    
    // ========== СЕССИИ ==========
    $router->add('POST', '/validate_token', 'SessionController', 'validateToken', $db, null);
    $router->add('GET', '/sessions', 'SessionController', 'getAllMySessions', $db, $auth);
    $router->add('DELETE', '/sessions/current', 'SessionController', 'terminateCurrentSession', $db, $auth);
    $router->add('DELETE', '/sessions/{session_id}', 'SessionController', 'terminateSession', $db, $auth);
    $router->add('DELETE', '/sessions', 'SessionController', 'terminateAllOtherSessions', $db, $auth);
    
    // ========== КОДЫ ==========
    $router->add('POST', '/codes/send', 'CodeController', 'sendCode', $db, null);
    $router->add('POST', '/codes/confirm', 'CodeController', 'confirmCode', $db, null);
    
    // ========== ПОИСК ==========
    $router->add('POST', '/search', 'SearchController', 'search', $db, $auth);
    
    // ========== УВЕДОМЛЕНИЯ ==========
    $router->add('GET', '/notifications/get/unread-count', 'NotificationController', 'getUnreadCount', $db, $auth);
    $router->add('POST', '/notifications/get', 'NotificationController', 'get', $db, $auth);
    $router->add('POST', '/notifications/mark-read', 'NotificationController', 'markRead', $db, $auth);
    
    // ========== СООБЩЕНИЯ ==========
    $router->add('POST', '/messages/get', 'MessageController', 'get', $db, $auth);
    $router->add('POST', '/messages/mark-read', 'MessageController', 'markRead', $db, $auth);
    $router->add('POST', '/messages/send', 'MessageController', 'send', $db, $auth);
    
    // ========== ЧАТЫ ==========
    $router->add('GET', '/chats/get/id/user/{user_id}', 'ChatController', 'getIdByUser', $db, $auth);
    $router->add('GET', '/chats/get/id/group/{user_id}', 'ChatController', 'getIdByGroup', $db, $auth);
    $router->add('GET', '/chats/get/unread-count', 'ChatController', 'getUnreadCount', $db, $auth);
    $router->add('POST', '/chats/get/info', 'ChatController', 'getInfo', $db, $auth);
    $router->add('GET', '/chats/get', 'ChatController', 'get', $db, $auth);
    $router->add('GET', '/chats/members/get/{chat_id}/ids', 'ChatController', 'getMembersIds', $db, $auth);

    // ========== ОТНОШЕНИЯ ==========
    $router->add('GET', '/relationships/list', 'RelationshipController', 'getList', $db, null);
    $router->add('GET', '/relationships/get/users/{user_id}', 'RelationshipController', 'getRelationshipUsers', $db, null);
    $router->add('GET', '/relationships/get/{user_id}/{related_user_id}', 'RelationshipController', 'getRelationshipWithUser', $db, null);
    $router->add('PUT', '/relationships/subscribe', 'RelationshipController', 'subscribe', $db, $auth);
    $router->add('DELETE', '/relationships/unsubscribe', 'RelationshipController', 'unsubscribe', $db, $auth);
    $router->add('PUT', '/relationships/block', 'RelationshipController', 'block', $db, $auth);
    $router->add('PUT', '/relationships/change-list', 'RelationshipController', 'changeList', $db, $auth);

    // ========== ПОЛЬЗОВАТЕЛИ ==========
    $router->add('GET', '/users/{id}', 'UserController', 'getUserById', $db, null);
    $router->add('GET', '/users/by-link/{linkname}', 'UserController', 'getUserByLinkname', $db, null);
    $router->add('PUT', '/users/update-profile', 'UserController', 'updateProfile', $db, $auth);
    
    // ========== НАВЫКИ ПОЛЬЗОВАТЕЛЕЙ ==========
    $router->add('POST', '/user-skills/add', 'UserSkillController', 'addUserSkill', $db, $auth);
    $router->add('PUT', '/user-skills/edit', 'UserSkillController', 'editUserSkill', $db, $auth);
    $router->add('DELETE', '/user-skills/delete', 'UserSkillController', 'deleteUserSkill', $db, $auth);
    $router->add('POST', '/user-skills/get/{user_id}', 'UserSkillController', 'getUserSkills', $db, null);
    $router->add('POST', '/user-skills/get', 'UserSkillController', 'getAllSkills', $db, $auth);
    $router->add('GET', '/user-skills/levels/get', 'UserSkillController', 'getSkillLevels', $db, $auth);
    $router->add('POST', '/user-skills/endorsement/add', 'UserSkillController', 'addEndorsementUserSkill', $db, $auth);
    $router->add('DELETE', '/user-skills/endorsement/delete', 'UserSkillController', 'deleteEndorsementUserSkill', $db, $auth);

    // ========== ГРУППЫ ==========
    $router->add('GET', '/groups/{group_id}', 'GroupController', 'getGroupById', $db, null);
    $router->add('GET', '/groups/by-link/{linkname}', 'GroupController', 'getGroupByLinkname', $db, null);
    $router->add('GET', '/groups/list/{user_id}', 'GroupController', 'getUserGroups', $db, null);
    $router->add('GET', '/groups/is-admin/{group_id}/{user_id}', 'GroupController', 'getUserIsAdminGroup', $db, null);
    $router->add('POST', '/groups/create', 'GroupController', 'createGroup', $db, $auth);
    $router->add('PUT', '/groups/edit', 'GroupController', 'editGroup', $db, $auth);
    $router->add('GET', '/groups/members/{group_id}', 'GroupController', 'members', $db, null);
    $router->add('GET', '/groups/status/subscribe/{group_id}', 'GroupController', 'statusSubscribe', $db, $auth);
    $router->add('POST', '/groups/subscribe', 'GroupController', 'subscribe', $db, $auth);
    $router->add('POST', '/groups/unsubscribe', 'GroupController', 'unsubscribe', $db, $auth);

    // ========== ПОСТЫ ==========
    $router->add('GET', '/posts/feed', 'PostController', 'getAllByFeed', $db, $auth);
    $router->add('GET', '/posts/user/{user_id}', 'PostController', 'getAllByUser', $db, null);
    $router->add('GET', '/posts/group/{group_id}', 'PostController', 'getAllByGroup', $db, null);
    $router->add('GET', '/post/get/{post_id}', 'PostController', 'get', $db, null);
    $router->add('POST', '/post/publicate', 'PostController', 'publicate', $db, $auth);
    $router->add('POST', '/post/delete', 'PostController', 'delete', $db, $auth);

    // ========== КОНТЕНТ ==========
    $router->add('GET', '/content/article/get/{article_id}', 'ContentController', 'articleGet', $db, null);
    $router->add('POST', '/content/article/create', 'ContentController', 'articleCreate', $db, $auth);
    $router->add('POST', '/file/upload', 'ContentController', 'fileUpload', $db, $auth);

    
    // =============== ЗАПУСКАЕМ МАРШРУТИЗАЦИЮ ===============
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
?>