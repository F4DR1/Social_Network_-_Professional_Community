<?php
    require_once 'core/Helpers.php';

    class AuthController {
        private $db;
        
        public function __construct($db, $auth = null) {
            $this->db = $db;
        }
        
        // POST /register
        public function register() {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Валидация
            if (empty($data['phone']) || empty($data['password']) || empty($data['lastname']) || empty($data['firstname'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Телефон, пароль и имя обязательны']);
                return;
            }

            // Приводим телефон к единому формату
            $cleanPhone = Helpers::formatPhone($data['phone']);

            // Проверка что номер не пустой
            if (strlen($cleanPhone) <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Не верный формат телефона']);
                return;
            }

            // Проверяем, есть ли уже такой телефон
            $exists = $this->db->fetchOne(
                "SELECT id FROM users WHERE phone = ?",
                [$cleanPhone]
            );
            
            if ($exists) {
                http_response_code(400);
                echo json_encode(['error' => 'Пользователь с таким номером телефона уже зарегистрирован']);
                return;
            }
            
            // Создаем токен для API
            $token = bin2hex(random_bytes(32));
            
            // Хешируем пароль
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Сохраняем в БД
            $this->db->query(
                "INSERT INTO users (phone, password_hash, lastname, firstname, api_token, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))",
                [$cleanPhone, $passwordHash, $data['lastname'], $data['firstname'], $token]
            );
            
            $userId = $this->db->lastInsertId();
            
            // Отдаем токен клиенту
            echo json_encode([
                'success' => true,
                'user_id' => $userId,
                'token' => $token
            ]);
        }
        
        // POST /login
        public function login() {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['login']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Логин и пароль обязательны']);
                return;
            }

            // Определяем, что ввели: email или телефон?
            $login = $data['login'];
            $user = null;
            
            // Проверяем, похоже ли на email (содержит @)
            if (Helpers::validateEmail($login)) {
                // Это email
                $user = $this->db->fetchOne(
                    "SELECT * FROM users WHERE email = ?",
                    [$login]
                );
            } else {
                // Считаем, что это телефон - приводим к единому формату
                $cleanPhone = Helpers::formatPhone($login);
                $user = $this->db->fetchOne(
                    "SELECT * FROM users WHERE phone = ?",
                    [$cleanPhone]
                );
            }
            
            // Проверяем пароль
            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Неверный логин или пароль']);
                return;
            }

            // Проверяем количество активных сессий
            $activeSessions = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM sessions WHERE user_id = ?",
                [$user['id']]
            )['count'];
            
            if ($activeSessions >= 5) {
                // Удаляем самую старую сессию
                $this->db->query(
                    "DELETE FROM sessions WHERE user_id = ? ORDER BY last_activity ASC LIMIT 1",
                    [$user['id']]
                );
            }
            
            // Получаем информацию об устройстве (передаем с клиента)
            $deviceInfo = $this->getDeviceInfo();
            
            // Генерируем новый токен для ЭТОЙ сессии
            $token = bin2hex(random_bytes(32));
            
            // Создаем запись в таблице sessions
            $this->db->query(
                "INSERT INTO sessions (user_id, token, device_name, device_type, ip_address, last_activity, created_at) 
                VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
                [
                    $user['id'],
                    $token,
                    $deviceInfo['name'],
                    $deviceInfo['type'],
                    $_SERVER['REMOTE_ADDR']
                ]
            );
            
            // Отдаем токен клиенту
            echo json_encode([
                'success' => true,
                'user_id' => $user['id'],
                'token' => $token,
                'session_id' => $this->db->lastInsertId()
            ]);
        }





        /**
         * Получает информацию об устройстве из User-Agent
         */
        private function getDeviceInfo() {
            // По умолчанию
            $deviceInfo = [
                'name' => 'Неизвестное устройство',
                'type' => 'web'
            ];
            
            // Получаем User-Agent
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            if (empty($userAgent)) {
                return $deviceInfo;
            }
            
            // Определяем тип устройства
            if (preg_match('/(mobile|iphone|ipod|android|blackberry|opera mini|iemobile|wp desktop)/i', $userAgent)) {
                $deviceInfo['type'] = 'mobile';
                $deviceInfo['name'] = $this->parseMobileDevice($userAgent);
            } elseif (preg_match('/(tablet|ipad|kindle|playbook)/i', $userAgent)) {
                $deviceInfo['type'] = 'tablet';
                $deviceInfo['name'] = $this->parseMobileDevice($userAgent);
            } else {
                $deviceInfo['type'] = 'desktop';
                $deviceInfo['name'] = $this->parseDesktopDevice($userAgent);
            }
            
            return $deviceInfo;
        }

        /**
         * Парсит мобильное устройство из User-Agent
         */
        private function parseMobileDevice($userAgent) {
            $deviceName = 'Мобильное устройство';
            
            // Определяем модель iPhone
            if (preg_match('/iPhone OS (\d+)_(\d+)/i', $userAgent, $matches)) {
                $deviceName = 'iPhone (iOS ' . $matches[1] . '.' . $matches[2] . ')';
            } elseif (preg_match('/iPhone/', $userAgent)) {
                $deviceName = 'iPhone';
            }
            
            // Определяем модель iPad
            if (preg_match('/iPad.*OS (\d+)_(\d+)/i', $userAgent, $matches)) {
                $deviceName = 'iPad (iOS ' . $matches[1] . '.' . $matches[2] . ')';
            } elseif (preg_match('/iPad/', $userAgent)) {
                $deviceName = 'iPad';
            }
            
            // Определяем Android устройство
            if (preg_match('/Android (\d+(?:\.\d+)?)/i', $userAgent, $matches)) {
                $androidVersion = $matches[1];
                
                // Пытаемся определить модель
                if (preg_match('/; ?([^;]+) Build/', $userAgent, $modelMatches)) {
                    $model = trim($modelMatches[1]);
                    $deviceName = $model . ' (Android ' . $androidVersion . ')';
                } else {
                    $deviceName = 'Android (версия ' . $androidVersion . ')';
                }
            }
            
            return $deviceName;
        }

        /**
         * Парсит десктопное устройство из User-Agent
         */
        private function parseDesktopDevice($userAgent) {
            $os = 'Unknown OS';
            $browser = 'Unknown Browser';
            
            // Определяем ОС
            if (preg_match('/Windows NT (\d+\.\d+)/i', $userAgent, $matches)) {
                $windowsVersions = [
                    '10.0' => 'Windows 10/11',
                    '6.3' => 'Windows 8.1',
                    '6.2' => 'Windows 8',
                    '6.1' => 'Windows 7',
                    '6.0' => 'Windows Vista',
                    '5.2' => 'Windows XP x64',
                    '5.1' => 'Windows XP'
                ];
                $version = $matches[1];
                $os = $windowsVersions[$version] ?? 'Windows ' . $version;
            } elseif (preg_match('/Mac OS X (\d+)[_.](\d+)/i', $userAgent, $matches)) {
                $os = 'macOS ' . $matches[1] . '.' . $matches[2];
            } elseif (preg_match('/Linux/i', $userAgent)) {
                $os = 'Linux';
            }
            
            // Определяем браузер
            if (preg_match('/Chrome\/(\d+)/i', $userAgent, $matches)) {
                $browser = 'Chrome ' . $matches[1];
            } elseif (preg_match('/Firefox\/(\d+)/i', $userAgent, $matches)) {
                $browser = 'Firefox ' . $matches[1];
            } elseif (preg_match('/Safari\/(\d+)/i', $userAgent, $matches)) {
                $browser = 'Safari';
            } elseif (preg_match('/Edge\/(\d+)/i', $userAgent, $matches)) {
                $browser = 'Edge ' . $matches[1];
            } elseif (preg_match('/MSIE (\d+)/i', $userAgent, $matches)) {
                $browser = 'Internet Explorer ' . $matches[1];
            }
            
            return $os . ', ' . $browser;
        }
    }
?>
