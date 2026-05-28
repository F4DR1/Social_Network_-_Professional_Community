<?php
    require_once 'core/Helpers.php';
    require_once 'core/DeviceDetector.php';

    class AuthController {
        private $db;
        
        public function __construct($db, $auth = null) {
            $this->db = $db;
        }
        

        
        /**
         * Поиск пользователя по логину (email или телефон)
         */
        public static function findUserByLogin($db, $login) {
            if (Helpers::validateEmail($login)) {
                return $db->fetchOne("
                        SELECT
                            id,
                            password_hash
                        FROM
                            users
                        WHERE
                            email = ?
                    ",
                    [$login]
                );
            } else {
                $cleanPhone = Helpers::formatPhone($login);
                return $db->fetchOne("
                        SELECT
                            id,
                            password_hash
                        FROM
                            users
                        WHERE
                            phone = ?
                    ",
                    [$cleanPhone]
                );
            }
        }
        
        /**
         * Авторизуем пользователя
         */
        public static function authorizeUser($db, $userId, $login) {
            // Создание сессии
            $token = Helpers::generateToken();
            $deviceInfo = DeviceDetector::getDeviceInfo();
            
            $db->query("
                    INSERT INTO sessions (user_id, token, device_name, device_type, ip_address, last_activity, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ",
                [
                    $userId,
                    $token,
                    $deviceInfo['name'],
                    $deviceInfo['type'],
                    DeviceDetector::getClientIP()
                ]
            );

            $sessionId = $db->lastInsertId();

        
            // Ответ в зависимости от типа клиента
            if (Helpers::isWebRequest()) {
                Helpers::setAuthCookie($token);
                Helpers::jsonResponse([
                    'success' => true,
                    'user_id' => $userId,
                    'session_id' => $sessionId
                ]);
            } else {
                Helpers::jsonResponse([
                    'success' => true,
                    'user_id' => $userId,
                    'token' => $token,
                    'session_id' => $sessionId
                ]);
            }
        }
        
        /**
         * Валидация данных
         */
        private static function validateData($db, $phone, $password, $lastname, $firstname) {
            // Валидация обязательных полей
            if (empty($phone) || empty($password) || empty($lastname) || empty($firstname)) {
                Helpers::errorResponse('Телефон, пароль и имя обязательны');
            }


            // Проверка сложности пароля
            $passwordCheck = Helpers::validatePassword($password);
            if ($passwordCheck !== true) {
                Helpers::errorResponse($passwordCheck);
            }

            // Форматируем телефон
            $cleanPhone = Helpers::formatPhone($phone);
            if (strlen($cleanPhone) <= 0) {
                Helpers::errorResponse('Неверный формат телефона');
            }

            // Проверка уникальности телефона
            $exists = $db->fetchOne("
                    SELECT
                        id
                    FROM
                        users
                    WHERE
                        phone = ?
                ",
                [$cleanPhone]
            );
            if ($exists) {
                Helpers::errorResponse('Пользователь с таким номером телефона уже зарегистрирован');
            }
        }





        /**
         * POST /auth/check - проверка токена и возврат данных пользователя
         */
        public function check() {
            $token = Helpers::extractToken();
            
            if (!$token) {
                Helpers::errorResponse('Токен не найден', 401);
            }
            
            // Проверяем сессию в БД
            $session = $this->db->fetchOne("
                    SELECT
                        *
                    FROM
                        sessions
                    WHERE
                        token = ?
                        AND
                        last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)
                ",
                [$token]
            );
            
            if (!$session) {
                Helpers::errorResponse('Сессия недействительна или истекла', 401);
            }
            
            // Обновляем last_activity
            $this->db->query("
                    UPDATE
                        sessions
                    SET
                        last_activity = NOW()
                    WHERE
                        token = ?
                ",
                [$token]
            );



            // Получаем данные пользователя
            $user = $this->db->fetchOne("
                    SELECT
                        u.id,
                        u.linkname,
                        u.phone,
                        u.lastname,
                        u.firstname,
                        CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                        f.file_path AS photo
                    FROM
                        sessions s
                        JOIN users u ON s.user_id = u.id
                        LEFT JOIN files f ON f.id = u.photo_id
                    WHERE
                        s.token = ?
                ",
                [$token]
            );

            $user['photo'] = Helpers::fileUrl($user['photo'] ?? Helpers::imagePlaceholder('user'));
            
            Helpers::jsonResponse(['success' => true, 'user' => $user]);
        }
        
        /**
         * POST /register
         */
        public function register() {
            $data = json_decode(file_get_contents('php://input'), true);

            $phone = $data['phone'] ?? null;
            $password = $data['password'] ?? null;
            $lastname = $data['lastname'] ?? null;
            $firstname = $data['firstname'] ?? null;
            
            // Валидация обязательных полей
            self::validateData($this->db, $phone, $password, $lastname, $firstname);

            
            // Телефон
            $cleanPhone = Helpers::formatPhone($phone);
            
            // Хэш пароля
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Создание пользователя
            $this->db->query("
                    INSERT INTO users (phone, password_hash, lastname, firstname, registered_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ",
                [$cleanPhone, $passwordHash, $data['lastname'], $data['firstname']]
            );
            
            Helpers::jsonResponse(['success' => true, 'user_id' => $this->db->lastInsertId()]);
        }
        
        /**
         * POST /register/validate
         */
        public function registerValidate() {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $phone = $data['phone'] ?? null;
            $password = $data['password'] ?? null;
            $lastname = $data['lastname'] ?? null;
            $firstname = $data['firstname'] ?? null;

            // Валидация обязательных полей
            self::validateData($this->db, $phone, $password, $lastname, $firstname);
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * POST /login
         */
        public function login() {
            $data = json_decode(file_get_contents('php://input'), true);

            $login = $data['login'] ?? null;
            $password = $data['password'] ?? null;
            
            if (empty($login) || empty($password)) {
                Helpers::errorResponse('Логин и пароль обязательны', 400);
            }


            // Поиск пользователя
            $user = $this->findUserByLogin($this->db, $login);
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                Helpers::errorResponse('Неверный логин или пароль', 401);
            }

            // Авторизация
            self::authorizeUser($this->db, $user['id'], $login);
        }
        
        /**
         * POST /logout
         */
        public function logout() {
            $token = Helpers::extractToken();
            
            if ($token) {
                $this->db->query("
                        DELETE
                        FROM
                            sessions
                        WHERE
                            token = ?
                    ",
                    [$token]
                );
                
                if (Helpers::isWebRequest()) {
                    Helpers::deleteAuthCookie();
                }
            }
            
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
