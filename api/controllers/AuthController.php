<?php
    require_once 'core/Helpers.php';
    require_once 'core/DeviceDetector.php';

    class AuthController {
        private $db;
        
        public function __construct($db, $auth = null) {
            $this->db = $db;
        }

        /**
         * POST /auth/check - проверка токена и возврат данных пользователя
         */
        public function check() {
            $token = Helpers::extractToken();
            
            if (!$token) {
                Helpers::errorResponse('Токен не найден', 401);
            }
            
            // Проверяем сессию в БД (используем логику из Auth.php)
            $session = $this->db->fetchOne(
                "SELECT s.*, u.id, u.linkname, u.phone, u.firstname, u.lastname, u.photo 
                FROM sessions s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.token = ? AND s.last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)",
                [$token]
            );
            
            if (!$session) {
                Helpers::errorResponse('Сессия недействительна или истекла', 401);
            }
            
            // Обновляем last_activity
            $this->db->query(
                "UPDATE sessions SET last_activity = NOW() WHERE token = ?",
                [$token]
            );
            
            Helpers::jsonResponse([
                'success' => true,
                'user_id' => $session['user_id'],
                'user' => [
                    'id' => $session['id'],
                    'linkname' => $session['linkname'],
                    'phone' => $session['phone'],
                    'firstname' => $session['firstname'],
                    'lastname' => $session['lastname'],
                    'photo' => $session['photo']
                ]
            ]);
        }
        
        /**
         * POST /register
         */
        public function register() {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Валидация обязательных полей
            if (empty($data['phone']) || empty($data['password']) || empty($data['lastname']) || empty($data['firstname'])) {
                Helpers::errorResponse('Телефон, пароль и имя обязательны');
            }

            // Проверка сложности пароля
            $passwordCheck = Helpers::validatePassword($data['password']);
            if ($passwordCheck !== true) {
                Helpers::errorResponse($passwordCheck);
            }

            // Форматируем телефон
            $cleanPhone = Helpers::formatPhone($data['phone']);
            if (strlen($cleanPhone) <= 0) {
                Helpers::errorResponse('Неверный формат телефона');
            }

            // Проверка уникальности телефона
            $exists = $this->db->fetchOne(
                "SELECT id FROM users WHERE phone = ?",
                [$cleanPhone]
            );
            if ($exists) {
                Helpers::errorResponse('Пользователь с таким номером телефона уже зарегистрирован');
            }
            
            // Создание пользователя
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $this->db->query(
                "INSERT INTO users (phone, password_hash, lastname, firstname, created_at) 
                VALUES (?, ?, ?, ?, NOW())",
                [$cleanPhone, $passwordHash, $data['lastname'], $data['firstname']]
            );
            
            Helpers::jsonResponse([
                'success' => true,
                'user_id' => $this->db->lastInsertId()
            ]);
        }
        
        /**
         * POST /login
         */
        public function login() {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['login']) || empty($data['password'])) {
                Helpers::errorResponse('Логин и пароль обязательны');
            }

            // Поиск пользователя
            $user = $this->findUserByLogin($data['login']);
            
            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                Helpers::errorResponse('Неверный логин или пароль', 401);
            }

            // Создание сессии
            $token = Helpers::generateToken();
            $deviceInfo = DeviceDetector::getDeviceInfo();
            
            $this->db->query(
                "INSERT INTO sessions (user_id, token, device_name, device_type, ip_address, last_activity, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $user['id'],
                    $token,
                    $deviceInfo['name'],
                    $deviceInfo['type'],
                    DeviceDetector::getClientIP()
                ]
            );

            $sessionId = $this->db->lastInsertId();
        
            // Ответ в зависимости от типа клиента
            if (Helpers::isWebRequest()) {
                Helpers::setAuthCookie($token);
                Helpers::jsonResponse([
                    'success' => true,
                    'user_id' => $user['id'],
                    'session_id' => $sessionId
                ]);
            } else {
                Helpers::jsonResponse([
                    'success' => true,
                    'user_id' => $user['id'],
                    'token' => $token,
                    'session_id' => $sessionId
                ]);
            }
        }
        
        /**
         * POST /logout
         */
        public function logout() {
            $token = Helpers::extractToken();
            
            if ($token) {
                $this->db->query("DELETE FROM sessions WHERE token = ?", [$token]);
                
                if (Helpers::isWebRequest()) {
                    Helpers::deleteAuthCookie();
                }
            }
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * Поиск пользователя по логину (email или телефон)
         */
        private function findUserByLogin($login) {
            if (Helpers::validateEmail($login)) {
                return $this->db->fetchOne(
                    "SELECT * FROM users WHERE email = ?",
                    [$login]
                );
            } else {
                $cleanPhone = Helpers::formatPhone($login);
                return $this->db->fetchOne(
                    "SELECT * FROM users WHERE phone = ?",
                    [$cleanPhone]
                );
            }
        }
    }
?>
