<?php
    require_once 'core/Helpers.php';
    require_once 'AuthController.php';

    class CodeController {
        private $db;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth = null) {
            $this->db = $db;
        }


        
        // Получить id назначения кода
        private static function getPurposeId($db, $purpose) {
            $raw = $db->fetchOne("
                    SELECT
                        id
                    FROM
                        code_purposes
                    WHERE
                        name = ?
                ",
                [$purpose]
            );
            if (empty($raw)) Helpers::errorResponse('Такого назначения для генерации кода нет.', 400);
            return $raw['id'];
        }
        
        // Сгенерировать хэш контакта
        public static function contactHash($isPhone, $phone, $email) {
            $rawContact = $isPhone ? $phone : $email;
            $type = $isPhone ? 'phone' : 'email';
            return hash('sha256', $type . ':' . $rawContact);
        }

        // Проверка, не отправляли ли код недавно (не чаще 1 раза в минуту)
        private static function checkRecentlySentCode($db, $userId, $contactHash, $purposeId) {
            $recent = $db->fetchOne("
                    SELECT
                        id
                    FROM
                        codes
                    WHERE
                        user_id <=> ?
                        AND
                        contact_hash = ?
                        AND
                        purpose_id = ?
                        AND
                        created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                        AND
                        is_used = 0
                ",
                [$userId, $contactHash, $purposeId]
            );
            if ($recent) Helpers::errorResponse('Код уже был отправлен. Подождите 1 минуту.', 429);
        }

        // Сгенерировать код
        public static function generateCode($db, $userId, $contactHash, $purpose) {
            // Получаем id метода из базы
            $purposeId = self::getPurposeId($db, $purpose);

            // Проверка, не отправляли ли код недавно
            self::checkRecentlySentCode($db, $userId, $contactHash, $purposeId);


            // Генерация 6-значного кода
            $code = sprintf('%06d', random_int(0, 999999));

            // Сохраняем код
            $db->query("
                    INSERT INTO codes (user_id, contact_hash, purpose_id, code, expires_at, created_at, is_used)
                    VALUES (?, ?, ?, ?, NOW() + INTERVAL 5 MINUTE, NOW(), 0)
                ",
                [$userId, $contactHash, $purposeId, $code]
            );
            
            return $code;
        }

        // Отправить SMS на телефон
        private static function sendSms($phone, $message) {
            // На локальной среде – просто логируем
            if (Helpers::isLocalhost()) {
                error_log("SMS to {$phone}: {$message}");
                return true;
            }
            // Здесь – реальная отправка через API
            // $apiKey = getenv('SMS_API_KEY');
            // ...
            return true;  // Временно возвращаем успех
        }

        // Отправить email
        private static function sendEmail($email, $message) {
            // На локальной среде – просто логируем
            if (Helpers::isLocalhost()) {
                error_log("Email to {$email}: {$message}");
                return true;
            }
            // Здесь – реальная отправка через почту
            
            return true;  // Временно возвращаем успех
        }




        
        /**
         * POST /code/send/ - отправить код пользователю
         */
        public function sendCode() {
            $data = json_decode(file_get_contents('php://input'), true);

            $login = $data['login'] ?? null;
            $purpose = $data['purpose'] ?? 'register_verification';

            if (empty($login)) Helpers::errorResponse('Не указаны данные для отправки кода.', 400);
            

            // Форматируем телефон
            $phone = Helpers::formatPhone($login);
            $isPhone = strlen($phone) > 0;

            // Формируем переменную Email для удобного обращения
            $email = $login;


            // Создаём хэш контакта
            $contactHash = self::contactHash($isPhone, $phone, $email);

            // Если назначение не регистрация - ищем пользователя
            $userId = null;
            if ($purpose != 'register_verification') {
                // Ищем пользователя по телефону/почте
                $user = AuthController::findUserByLogin($this->db, $login);
                if (empty($user)) Helpers::errorResponse('Пользователя с такими телефоном/почтой не найден.', 404);
                $userId = $user['id'];
            } else {
                // Проверка что при регистрации ввели номер
                if (!$isPhone) Helpers::errorResponse('При регистрации нужно указать номер телефона.', 400);
            }


            // Генерируем код
            $code = self::generateCode($this->db, $userId, $contactHash, $purpose);
            if (empty($code)) Helpers::errorResponse('Не удалось сгенерировать код для отправки.', 400);
            

            // Отправляем код пользователю
            $isSuccess = false;
            $resultMessage = '';
            if ($isPhone) {
                // Отправляем SMS
                $message = "Ваш код подтверждения: {$code}. Действителен 5 минут.";
                $isSuccess = self::sendSms($phone, $message);
                $phoneMasked = Helpers::maskPhone($phone);
                $resultMessage = "Код был отправлен в SMS по телефону: $phoneMasked.";
            } else {
                // Отправляем Email
                $message = "Ваш код подтверждения: {$code}. Действителен 5 минут.";
                $isSuccess = self::sendEmail($email, $message);
                $emailMasked = Helpers::maskEmail($email);
                $resultMessage = "Код был отправлен на почту: $emailMasked.";
            }
            

            if ($isSuccess) Helpers::jsonResponse(['success' => true, 'message' => $resultMessage, 'code' => $code]);  // Временно отправляем код на фронт
            else Helpers::errorResponse('Не удалось отправить код.', 502);
        }
        
        /**
         * POST /code/confirm/ - подтвердить отправленный пользователю код
         */
        public function confirmCode() {
            $data = json_decode(file_get_contents('php://input'), true);

            $purpose = $data['purpose'] ?? 'register_verification';
            $code = $data['code'] ?? null;
            $login = $data['login'] ?? null;

            // Валидация обязательных полей
            if (empty($code)) Helpers::errorResponse('Не указан код подтверждения.', 400);


            // Определяем тип контакта
            $phone = Helpers::formatPhone($login);
            $isPhone = strlen($phone) > 0;

            $email = $login;

            $contactHash = self::contactHash($isPhone, $phone, $email);


            // ID назначения кода
            $purposeId = self::getPurposeId($this->db, $purpose);

            // Определяем user_id (если есть)
            $userId = null;
            if ($purpose !== 'register_verification') {
                $user = AuthController::findUserByLogin($this->db, $login);
                if (empty($user)) {
                    Helpers::errorResponse('Пользователь не найден.', 404);
                }
                $userId = (int) $user['id'];
            }

            // Ищем неиспользованный код
            $record = $this->db->fetchOne("
                    SELECT
                        id,
                        expires_at,
                        is_used
                    FROM
                        codes
                    WHERE
                        user_id <=> ?
                        AND
                        contact_hash = ?
                        AND
                        purpose_id = ?
                        AND
                        code = ?
                        AND
                        is_used = 0
                        AND
                        expires_at > NOW()
                    LIMIT 1
                ",
                [$userId, $contactHash, $purposeId, $code]
            );

            if (!$record) {
                Helpers::errorResponse('Неверный код или срок его действия истёк.', 400);
            }

            // Помечаем код как использованный
            $this->db->query("
                    UPDATE
                        codes
                    SET
                        is_used = 1
                    WHERE
                        id = ?
                ",
                [$record['id']]
            );


            // Если это восстановление аккаунта - авторизовываем
            if ($purpose === 'recovery_verification') {
                if (empty($login)) Helpers::errorResponse('Не указан логин (телефон или email).', 400);
                // Авторизуем пользователя (восстановление доступа)
                AuthController::authorizeUser($this->db, $userId, $login);
            }

            Helpers::jsonResponse(['success' => true, 'message' => 'Код подтверждён.']);
        }
    }
?>
