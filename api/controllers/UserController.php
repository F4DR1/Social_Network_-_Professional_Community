<?php
    class UserController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        
        // GET /users/{id}
        public function getProfile($id) {
            // Проверяем авторизацию
            $this->auth->check();
            
            // Получаем пользователя из БД
            $user = $this->db->fetchOne(
                "SELECT id, linkname, lastname, firstname, photo, phone, email FROM users WHERE id = ?",
                [$id]
            );
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Пользователь не найден']);
                return;
            }
            
            // Отдаем JSON
            header('Content-Type: application/json');
            echo json_encode($user);
        }
        
        // PUT /users/{id}
        public function updateProfile($id) {
            // Проверяем авторизацию
            $this->auth->check();
            $currentUser = $this->auth->user();
            
            // Проверяем, имеет ли право этот пользователь редактировать профиль
            if ($currentUser['id'] != $id) {
                http_response_code(403);
                echo json_encode(['error' => 'Нельзя редактировать чужой профиль']);
                return;
            }
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Обновляем в БД
            $this->db->query(
                "UPDATE users SET name = ? WHERE id = ?",
                [$data['name'], $id]
            );
            
            echo json_encode(['success' => true]);
        }
    }
?>
