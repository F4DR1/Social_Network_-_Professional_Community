<?php
    require_once 'core/Helpers.php';

    class UserController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        
        /**
         * GET /users/{user_id} - получить данные пользователя по id
         */
        public function getUserById($userId) {
            Helpers::validateUserId($userId);

            $user = $this->db->fetchOne("
                    SELECT id, linkname, lastname, firstname, photo, phone, email
                    FROM users
                    WHERE id = ?
                ",
                [$userId]
            );
            
            if (!$user) {
                Helpers::errorResponse('Пользователь не найден', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'user' => $user]);
        }
        
        /**
         * GET /users/{linkname} - получить данные пользователя по linkname
         */
        public function getUserByLinkname($linkname) {
            $user = $this->db->fetchOne("
                    SELECT id, linkname, lastname, firstname, photo, phone, email
                    FROM users
                    WHERE linkname = ?
                ",
                [$linkname]
            );
            
            if (!$user) {
                Helpers::errorResponse('Пользователь не найден', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'user' => $user]);
        }
        
        /**
         * PUT /users - обновить данные пользователя
         */
        public function updateProfile() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            // // Обновляем в БД
            // $this->db->query(
            //     "UPDATE users SET name = ? WHERE id = ?",
            //     [$data['name'], $currentUser['id']]
            // );
            
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
