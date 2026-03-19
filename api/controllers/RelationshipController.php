<?php
    require_once 'core/Helpers.php';

    class RelationshipController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }

        /**
         * Универсальный метод для upsert операций
         */
        private function upsertRelation($relatedUserId, $data) {
            try {
                $this->auth->check();
                $currentUser = $this->auth->getCurrentUser();
                
                $fields = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $updates = implode(', ', array_map(fn($f) => "$f = VALUES($f)", $fields));
                
                $sql = "INSERT INTO relationships (user_id, related_user_id, " . implode(', ', $fields) . ", created_at) 
                    VALUES (?, ?, $placeholders, NOW())
                    ON DUPLICATE KEY UPDATE $updates";
                
                $params = array_merge([$currentUser['id'], $relatedUserId], array_values($data));
                
                $this->db->query($sql, $params);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка отношений', 409);
            }
        }


        
        /**
         * GET /relationships/list - получить данные список всех доступных взаимоотношений
         */
        public function getList() {
            $list = $this->db->fetchAll(
                "SELECT * FROM relationship_lists",
                []
            );
            
            if (!$list) {
                Helpers::errorResponse('Список не найден', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'list' => $list]);
        }
        
        /**
         * GET /relationships/get/{user_id}/{related_user_id} - получить отношение пользователя к другому пользователю
         */
        public function getRelationship($userId, $relatedUserId) {
            $relationship = $this->db->fetchOne(
                "SELECT * FROM relationships WHERE user_id = ? AND related_user_id = ?",
                [$userId, $relatedUserId]
            );
            
            Helpers::jsonResponse([
                'success' => true,
                'relationship' => $relationship ?: null
            ]);
        }
        
        /**
         * PUT /relationships/subscribe - создать отношение текущего пользователя к другому пользователю
         */
        public function subscribe() {
            $data = json_decode(file_get_contents('php://input'), true);
            $this->upsertRelation($data['related_user_id'], ['is_blocked' => false]);
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * DELETE /relationships/unsubscribe - удалить отношение текущего пользователя к другому пользователю
         */
        public function unsubscribe() {
            $this->auth->check();
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $this->auth->check();
                $currentUser = $this->auth->getCurrentUser();

                $this->db->query(
                    "DELETE FROM relationships WHERE user_id = ? AND related_user_id = ?",
                    [$currentUser['id'], $data['related_user_id']]
                );
                
                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка отношений', 409);
            }
        }

        /**
         * PUT /relationships/block - создать отношение текущего пользователя к другому пользователю со значением is_blocked
         */
        public function block() {
            $data = json_decode(file_get_contents('php://input'), true);
            $this->upsertRelation($data['related_user_id'], ['is_blocked' => $data['is_blocked']]);
            Helpers::jsonResponse(['success' => true]);
        }

        /**
         * PUT /relationships/change-list - создать отношение текущего пользователя к другому пользователю со значением relationship_list_id
         */
        public function changeList() {
            $data = json_decode(file_get_contents('php://input'), true);
            $this->upsertRelation($data['related_user_id'], ['relationship_list_id' => $data['list_id']]);
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
