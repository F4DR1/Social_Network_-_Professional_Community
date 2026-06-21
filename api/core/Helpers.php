<?php
    use League\CommonMark\CommonMarkConverter;
    require_once 'DeviceDetector.php';

    class Helpers {

        /**
         * Проверяет межсерверный ли запрос
         */
        public static function isInternalRequest(): bool {
            $headers = getallheaders();
            $apiKey = $headers['X-Api-Key'] ?? '';
            return $apiKey === env('INTERNAL_API_KEY');
        }
        
        /**
         * Проверка на локальный сервер
         */
        public static function isLocalhost() {
            $host = $_SERVER['SERVER_NAME'] ?? '';
            return $host === 'localhost' || strpos($host, '.local') !== false;
        }



        /**
         * Возвращает основной домен (без протокола и поддоменов)
         */
        public static function getMainDomain() {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            // Убираем поддомен api.
            $host = preg_replace('/^api\./', '', $host);
            return $host ?: 'localhost';
        }

        /**
         * Возвращает базовый URL API
         */
        public static function apiBaseUrl() {
            if ($_SERVER['HTTP_HOST'] === 'localhost') {
                return 'http://localhost/social_network/api';
            }
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }

        /**
         * Формирует полный путь для файлов
         */
        public static function fileUrl($relativePath) {
            if (empty($relativePath)) return null;
            return self::apiBaseUrl() . '/' . ltrim($relativePath, '/');
        }



    



        /**
         * Возвращает список ролей администраторов групп
         */
        public static function getGroupAdminRoles() {
            $roles = ['owner', 'admin', 'moderator'];
            return [
                'roles' => $roles,
                'names' => implode(',', array_fill(0, count($roles), '?'))
            ];
        }

        /**
         * Формирует заглушки для изображений
         */
        public static function imagePlaceholder($type) {
            switch ($type) {
                case 'user':
                    return 'images/static/user_empty.webp';
                    
                case 'group':
                    return 'images/static/group_empty.webp';
                    
                case 'banner':
                    return 'images/static/banner_empty.webp';
                
                default:
                    // Общая заглушка на изображение
                    return 'images/static/user_empty.webp';
            }
        }

        /**
         * Конвертирует markdown в html
         */
        public static function markdownToHtml(string $markdown): string {
            $converter = new CommonMarkConverter([
                'html_input' => 'escape',  // Запрещает вставку чистого HTML
                'allow_unsafe_links' => false,
            ]);
            return $converter->convert($markdown)->getContent();
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
         * Получение домена для cookie
         */
        private static function getCookieDomain() {
            $domain = self::getMainDomain();
            return ($domain === 'localhost') ? $domain : '.' . $domain;
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
                'samesite' => 'Lax'
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
         * Маскирует email: оставляет первый и последний символ локальной части,
         * а также домен целиком. Пример: "example@mail.com" → "e***e@mail.com"
         */
        public static function maskEmail(string $email): string {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'некорректный email';
            }

            [$local, $domain] = explode('@', $email, 2);
            $len = mb_strlen($local);

            if ($len <= 2) {
                // Если локальная часть из 1-2 символов, показываем только первую букву
                $maskedLocal = mb_substr($local, 0, 1) . '***';
            } else {
                $first = mb_substr($local, 0, 1);
                $last = mb_substr($local, -1);
                $maskedLocal = $first . str_repeat('*', max($len - 2, 1)) . $last;
            }

            return $maskedLocal . '@' . $domain;
        }

        /**
         * Маскирует номер телефона: показывает код страны, оператора и последние 2 цифры.
         * Ожидает номер в формате, который возвращает Helpers::formatPhone()
         * (например, "79161234567" или "+79161234567").
         * Пример: "+79161234567" → "+7 (916) ***-**-67"
         */
        public static function maskPhone(string $phone): string {
            // Убираем все нецифровые символы для единообразия
            $digits = preg_replace('/\D/', '', $phone);
            $len = strlen($digits);

            if ($len < 7) {
                // Слишком короткий номер – маскируем почти всё
                return preg_replace('/\d/', '*', $phone);
            }

            // Предполагаем российскую нумерацию: 1 цифра кода страны, 3 – код оператора, 7 – номер
            $countryCode = substr($digits, 0, 1);   // 7
            $operator = substr($digits, 1, 3);      // 916
            $number = substr($digits, 4);           // 1234567
            $lastTwo = substr($number, -2);         // 67

            $maskedNumber = '***-**-' . $lastTwo;

            return "+{$countryCode} ({$operator}) {$maskedNumber}";
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
            if (strlen($password) > 30) {
                return 'Пароль должен быть не более 30 символов';
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
         * Проверяет, соответствует ли linkname верному формату (не содержит зарезервированные имена).
         * 
         * @param string $linkname  Проверяемый идентификатор
         *
         * @return bool true, если linkname соответствует верному формату
         */
        public static function isValidLinknameFormat(string $linkname): bool {
            if (preg_match('/^(user|group)(\d+)$/', $linkname, $matches)) {
                return false;
            }
            return true;
        }

        /**
         * Проверяет, уникален ли linkname в таблицах users и groups.
         *
         * @param Database $db  Подключение к БД
         * @param string $linkname  Проверяемый идентификатор
         * @param int|null $excludeUserId  ID пользователя, которого нужно игнорировать (при редактировании)
         * @param int|null $excludeGroupId  ID группы, которую нужно игнорировать (при редактировании)
         *
         * @return bool true, если linkname не используется ни одним пользователем / группой
         */
        public static function isLinknameUnique($db, string $linkname, ?int $excludeUserId = null, ?int $excludeGroupId = null): bool {
            if (empty($linkname)) {
                return false;
            }

            // Проверка среди пользователей
            $userSql = "SELECT COUNT(*) AS cnt FROM users WHERE linkname = ?";
            $params = [$linkname];
            if ($excludeUserId !== null) {
                $userSql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            $userCount = (int) ($db->fetchOne($userSql, $params)['cnt'] ?? 0);

            // Проверка среди групп
            $groupSql = "SELECT COUNT(*) AS cnt FROM `groups` WHERE linkname = ?";
            $params = [$linkname];
            if ($excludeGroupId !== null) {
                $groupSql .= " AND id != ?";
                $params[] = $excludeGroupId;
            }
            $groupCount = (int) ($db->fetchOne($groupSql, $params)['cnt'] ?? 0);

            return ($userCount + $groupCount) === 0;
        }


        
        /**
         * Проверить ссылку на верную длину
         */
        public static function validateLinknameLength(string $name) {
            if (empty($name) || strlen($name) < 4) Helpers::errorResponse('Ссылка должна содержать минимум 4 символа', 400);
            if (strlen($name) > 50) Helpers::errorResponse('Ссылка должна содержать максимум 50 символов', 400);
        }

        /**
         * Проверить имя на верную длину
         */
        public static function validateNameLength(string $name) {
            if (empty($name) || strlen($name) < 4) Helpers::errorResponse('Название должно содержать минимум 4 символа', 400);
            if (strlen($name) > 100) Helpers::errorResponse('Название должно содержать максимум 100 символов', 400);
        }





        /**
         * Проверка на корректный id
         */
        public static function validateId($id, $text) {
            if (empty($id) || !is_numeric($id) || $id <= 0) {
                self::errorResponse($text, 400);
            }
        }



        /**
         * Проверить id сессии
         */
        public static function validateSessionId($sessionId) {
            self::validateId($sessionId, 'Неверный ID сессии');
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
         * Проверить id чата
         */
        public static function validateChatId($chatId) {
            self::validateId($chatId, 'Неверный ID чата');
        }
        
        /**
         * Проверить id сообщения
         */
        public static function validateMessageId($messageId) {
            self::validateId($messageId, 'Неверный ID сообщения');
        }
        
        /**
         * Проверить id поста
         */
        public static function validatePostId($userId) {
            self::validateId($userId, 'Неверный ID поста');
        }
        
        /**
         * Проверить id скилла
         */
        public static function validateSkillId($skillId) {
            self::validateId($skillId, 'Неверный ID навыка');
        }
        
        /**
         * Проверить id уровня скилла
         */
        public static function validateSkillLevelId($skillLevelId) {
            self::validateId($skillLevelId, 'Неверный ID уровня навыка');
        }
    }
?>
