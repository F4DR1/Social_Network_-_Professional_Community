<?php
    class Router {
        private $routes = [];
        
        // Добавить маршрут в таблицу маршрутов
        public function add($method, $path, $controller, $action, $db, $auth) {
            $this->routes[] = [
                'method' => $method,
                'path' => $path,
                'controller' => $controller,
                'action' => $action,
                'db' => $db,
                'auth' => $auth
            ];
        }
        
        // Запустить поиск подходящего маршрута
        public function dispatch($method, $uri) {
            // Убираем GET-параметры из URL (/?id=5 -> /)
            // $uri = parse_url($uri, PHP_URL_PATH);
            $scriptName = dirname($_SERVER['SCRIPT_NAME']);
            $scriptName = rtrim($scriptName, '/');

            if (strpos($uri, $scriptName) === 0) {
                $uri = substr($uri, strlen($scriptName));
            }
            
            // Убираем GET-параметры
            $uri = parse_url($uri, PHP_URL_PATH);
            $uri = trim($uri, '/');  // Убираем начальный слеш
            
            // error_log("Clean URI: " . $uri);
            
            
            // Ищем подходящий маршрут
            foreach ($this->routes as $route) {
                // Совпадает метод? (GET/POST)
                if ($route['method'] !== $method) continue;
                
                // Превращаем /users/{id} в регулярное выражение
                $pattern = $this->convertToRegex($route['path']);
                
                if (preg_match($pattern, $uri, $matches)) {
                    // Убираем полное совпадение, оставляем только параметры
                    array_shift($matches);
                    
                    // Подключаем файл с контроллером
                    require_once 'controllers/' . $route['controller'] . '.php';
                    
                    // Создаем объект контроллера
                    $controller = new $route['controller']($route['db'], $route['auth']);
                    
                    // Вызываем нужный метод и передаем параметры из URL
                    call_user_func_array([$controller, $route['action']], $matches);
                    return;
                }
            }
            
            // Ничего не нашли - 404
            http_response_code(404);
            echo json_encode(['error' => 'Маршрут не найден']);
        }
        
        // Конвертирует /users/{id} в регулярку /^\/users\/([^\/]+)$/
        private function convertToRegex($path) {
            // Убираем начальный слеш из пути роута
            $path = trim($path, '/');  
            
            // Заменяем {id} на capturing group
            $path = preg_replace('/{([^}]+)}/', '([^/]+)', $path);
            
            // Экранируем слеши (если есть сегменты)
            $path = str_replace('/', '\\/', $path);
            
            // Формируем финальный паттерн
            return '#^' . $path . '$#';
        }
    }
?>
