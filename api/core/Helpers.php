<?php
    require_once 'DeviceDetector.php';

    class Helpers {

        /**
         * Проверка на корректный id
         */
        private static function validateId($id, $text) {
            if (empty($id) || !is_numeric($id) || $id <= 0) {
                self::errorResponse($text, 400);
            }
        }
        
        /**
         * Получение домена для cookie
         */
        private static function getCookieDomain() {
            $domain = $_SERVER['HTTP_HOST'] ?? '';
            $domain = preg_replace('/^www\./', '', $domain);
            $domain = preg_replace('/:\d+$/', '', $domain);
            return $domain;
        }
        
        /**
         * Проверка на локальный сервер
         */
        private static function isLocalhost() {
            $host = $_SERVER['SERVER_NAME'] ?? '';
            return $host === 'localhost' || strpos($host, '.local') !== false;
        }

    

        /**
         * Возвращает список ролей администраторов групп
         */
        public static function getGroupAdminRoles() {
            $roles = ['owner', 'admin', 'moderator'];
            return [
                'roles' => $roles,
                'text' => implode(',', array_fill(0, count($roles), '?'))
            ];
        }
        
        /**
         * Генерация случайного токена
         */
        public static function generateToken() {
            return bin2hex(random_bytes(32));
        }
        
        /**
         * Извлекает токен из запроса
         */
        public static function extractToken() {
            $token = $_COOKIE['auth_token'] ?? '';
            
            if (!$token) {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? '';
                if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                    $token = $matches[1];
                }
            }
            
            return $token;
        }
        
        /**
         * Проверяет, является ли запрос из веб-браузера
         */
        public static function isWebRequest() {
            $headers = getallheaders();
            
            if (isset($headers['X-Client-Type'])) {
                return $headers['X-Client-Type'] !== 'mobile';
            }
            
            if (isset($headers['User-Agent'])) {
                $ua = $headers['User-Agent'];
                if (preg_match('/okhttp|Dalvik|Java|Apache-HttpClient|Unity|curl|python|Postman/i', $ua)) {
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * Устанавливает HttpOnly cookie
         */
        public static function setAuthCookie($token) {
            $domain = self::getCookieDomain();
            
            setcookie('auth_token', $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'domain' => $domain,
                'secure' => !self::isLocalhost(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        /**
         * Удаляет cookie
         */
        public static function deleteAuthCookie() {
            $domain = self::getCookieDomain();
            
            setcookie('auth_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => !self::isLocalhost(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        /**
         * Форматирует телефон
         */
        public static function formatPhone($phone) {
            $cleaned = preg_replace('/[^0-9]/', '', $phone);
            
            if (strlen($cleaned) == 11 && $cleaned[0] == '8') {
                $cleaned[0] = '7';
            }
            
            if (strlen($cleaned) == 10 && $cleaned[0] == '9') {
                $cleaned = '7' . $cleaned;
            }
            
            return $cleaned;
        }
        
        /**
         * JSON ответ
         */
        public static function jsonResponse($data, $statusCode = 200) {
            array_merge($data);
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        /**
         * Ошибка в JSON
         */
        public static function errorResponse($message, $statusCode = 400) {
            self::jsonResponse(['success' => false, 'error' => $message], $statusCode);
        }
        
        /**
         * Валидация email
         */
        public static function validateEmail($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
        
        /**
         * Проверка пароля (сложность)
         */
        public static function validatePassword($password) {
            if (strlen($password) < 6) {
                return 'Пароль должен быть не менее 6 символов';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                return 'Пароль должен содержать хотя бы одну заглавную букву';
            }
            if (!preg_match('/[a-z]/', $password)) {
                return 'Пароль должен содержать хотя бы одну строчную букву';
            }
            if (!preg_match('/[0-9]/', $password)) {
                return 'Пароль должен содержать хотя бы одну цифру';
            }
            return true;
        }
        
        /**
         * Проверить id пользователя
         */
        public static function validateUserId($userId) {
            self::validateId($userId, 'Неверный ID пользователя');
        }
        
        /**
         * Проверить id группы
         */
        public static function validateGroupId($userId) {
            self::validateId($userId, 'Неверный ID группы');
        }
        
        /**
         * Проверить id поста
         */
        public static function validatePostId($userId) {
            self::validateId($userId, 'Неверный ID поста');
        }
    }
?>
