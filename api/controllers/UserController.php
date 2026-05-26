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
         * Общий обработчик поиска пользователя
         */
        private function getUser($user) {
            if (!$user) {
                Helpers::errorResponse('Пользователь не найден', 404);
            }
            
            $user['photo'] = Helpers::fileUrl($user['photo'] ?? Helpers::imagePlaceholder('user'));
            
            Helpers::jsonResponse(['success' => true, 'user' => $user]);
        }



        /**
         * GET /users/{user_id} - получить данные пользователя по id
         */
        public function getUserById($userId) {
            Helpers::validateUserId($userId);

            $user = $this->db->fetchOne("
                    SELECT
                        u.id,
                        u.linkname,
                        u.lastname,
                        u.firstname,
                        CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                        f.file_path AS photo,
                        u.phone,
                        u.email
                    FROM
                        users u
                        LEFT JOIN files f ON f.id = u.photo_id
                    WHERE
                        u.id = ?
                ",
                [$userId]
            );
            $this->getUser($user);
        }
        
        /**
         * GET /users/{linkname} - получить данные пользователя по linkname
         */
        public function getUserByLinkname($linkname) {
            $user = $this->db->fetchOne("
                    SELECT
                        u.id,
                        u.linkname,
                        u.lastname,
                        u.firstname,
                        CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                        f.file_path AS photo,
                        u.phone,
                        u.email
                    FROM
                        users u
                        LEFT JOIN files f ON f.id = u.photo_id
                    WHERE
                        u.linkname = ?
                ",
                [$linkname]
            );
            $this->getUser($user);
        }
        
        /**
         * PUT /users/update - обновить данные пользователя
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
