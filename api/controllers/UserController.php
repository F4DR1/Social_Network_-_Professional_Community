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
         * Выполняет запрос пользователя по переданному условию и параметрам,
         * затем обрабатывает и отпраляет ответ.
         */
        private function fetchUser(string $where, array $params): void {
            $sql = "
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
                    $where
            ";
            $user = $this->db->fetchOne($sql, $params);
            if (!$user) Helpers::errorResponse('Пользователь не найден', 404);
            
            $user['photo'] = Helpers::fileUrl($user['photo'] ?? Helpers::imagePlaceholder('user'));
            
            Helpers::jsonResponse(['success' => true, 'user' => $user]);
        }





        /**
         * GET /users/{user_id} - получить данные пользователя по id
         */
        public function getUserById($userId) {
            Helpers::validateUserId($userId);
            $this->fetchUser('u.id = ?', [$userId]);
        }
        
        /**
         * GET /users/{linkname} - получить данные пользователя по linkname
         */
        public function getUserByLinkname($linkname) {
            $this->fetchUser("u.linkname = ?", [$linkname]);
        }
        
        /**
         * POST /users/update-profile - редактировать профиль пользователя
         */
        public function updateProfile() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $data['userId'] ?? null;
            $category = $data['category'] ?? null;
            
            Helpers::validateUserId($userId);
            if ($userId !== $currentUserId) Helpers::errorResponse('Редактировать можно только свой профиль!', 400);
            
            
            try {
                switch ($category) {
                    case 'base':
                        // Изменяем базовую информацию
                        $baseJson = $data['base'] ?? null;
                        $base = json_decode($baseJson, true);

                        if (!$base) Helpers::errorResponse('Неверные данные.', 400);


                        $userLinkname = trim($base['linkname']);

                        // Валидация входных данных
                        Helpers::validateLinknameLength($userLinkname);


                        // Проверяем ссылку
                        $noneLinkname = "user$userId";
                        if ($userLinkname === $noneLinkname) {
                            $userLinkname = null;
                        } else {
                            // Проверка ссылки на верный формат
                            if (!Helpers::isValidLinknameFormat($userLinkname))
                                Helpers::errorResponse('Ссылка не должна быть формата "user123" или "group123"!', 400);
                            
                            // Проверка ссылки на занятость
                            if (!Helpers::isLinknameUnique($this->db, $userLinkname, excludeUserId: $userId))
                                Helpers::errorResponse('Ссылка уже занята.', 400);
                        }


                        // Обновляем данные
                        $this->db->query("
                                UPDATE
                                    `users`
                                SET
                                    linkname = ? 
                                WHERE
                                    id = ?
                            ",
                            [$userLinkname, $userId]
                        );
                        
                        Helpers::jsonResponse(['success' => true, 'linkname' => $userLinkname ?: $noneLinkname]);
                        break;
                    
                    default:
                        Helpers::errorResponse('Не удалось получить данные для редактирования.', 400);
                        break;
                }

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка редактирования профиля', 500);
            }
        }
    }
?>
