<?php
    // Разрешаем запросы с любого сайта (для разработки)
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // Если это предварительный запрос OPTIONS (браузер так проверяет CORS) - просто выходим
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit();
    }

    
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
    $router->add('POST', '/login', 'AuthController', 'login', $db, null);
    $router->add('POST', '/register', 'AuthController', 'register', $db, null);
    
    // Сессии
    $router->add('GET', '/sessions', 'SessionController', 'getMySessions', $db, $auth);
    $router->add('DELETE', '/sessions/{id}', 'SessionController', 'terminateSession', $db, $auth);
    $router->add('DELETE', '/sessions', 'SessionController', 'terminateAllOtherSessions', $db, $auth);

    // Пользователи
    $router->add('GET', '/users/{id}', 'UserController', 'getProfile', $db, $auth);
    $router->add('PUT', '/users/{id}', 'UserController', 'updateProfile', $db, $auth);

    // // Посты
    // $router->add('POST', '/posts', 'PostController', 'create', $db, $auth);
    // $router->add('GET', '/feed', 'FeedController', 'getFeed', $db, $auth);

    
    // =============== ЗАПУСКАЕМ МАРШРУТИЗАЦИЮ ===============
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
?>