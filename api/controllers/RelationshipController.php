<?php
    require_once 'core/Helpers.php';
    require_once 'core/Notifications.php';
    use Core\Redis;

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
            Helpers::validateUserId($relatedUserId);

            try {
                $currentUser = $this->auth->getCurrentUser();
                
                $fields = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $updates = implode(', ', array_map(fn($f) => "$f = VALUES($f)", $fields));
                
                $sql = "
                    INSERT INTO relationships (user_id, related_user_id, " . implode(', ', $fields) . ", created_at) 
                    VALUES (?, ?, $placeholders, NOW())
                    ON DUPLICATE KEY UPDATE $updates
                ";
                
                $params = array_merge([$currentUser['id'], $relatedUserId], array_values($data));
                
                $this->db->query($sql, $params);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка отношений', 409);
            }
        }

        /**
         * Универсальный метод для delete операций
         */
        private function deleteRelation($relatedUserId) {
            Helpers::validateUserId($relatedUserId);

            try {
                $currentUser = $this->auth->getCurrentUser();
                
                $this->db->query("
                        DELETE
                        FROM
                            relationships
                        WHERE
                            user_id = ?
                            AND
                            related_user_id = ?
                    ",
                    [$currentUser['id'], $relatedUserId]
                );

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка отношений', 409);
            }
        }


        
        /**
         * GET /relationships/list - получить данные список всех доступных взаимоотношений
         */
        public function getList() {
            $list = $this->db->fetchAll("
                    SELECT
                        *
                    FROM
                        relationship_lists
                ",
                []
            );
            
            if (!$list) {
                Helpers::errorResponse('Список не найден', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'list' => $list]);
        }
        
        /**
         * GET /relationships/get/users/{user_id} - получить списки всех пользователей с которыми есть отношения
         */
        public function getRelationshipUsers($userId) {
            Helpers::validateUserId($userId);
            
            try {
                $sql = "
                    SELECT DISTINCT
                        u.id,
                        u.linkname,
                        CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                        f.file_path as photo,
                        COALESCE(r1.is_blocked, 0) as my_relation_blocked,
                        COALESCE(r2.is_blocked, 0) as their_relation_blocked,
                        CASE 
                            -- Mutual: ОБЕ записи существуют И не заблокированы
                            WHEN
                                r1.id IS NOT NULL
                                AND
                                r2.id IS NOT NULL 
                                AND
                                r1.is_blocked = 0
                                AND
                                r2.is_blocked = 0
                            THEN 'mutual'
                            
                            -- Outgoing: МОЯ запись есть, ЧУЖОЙ нет
                            WHEN
                                r1.id IS NOT NULL
                                AND
                                r2.id IS NULL 
                                AND
                                r1.is_blocked = 0
                            THEN 'outgoing'
                            
                            -- Incoming: ЧЖУЖАЯ запись есть, МОЕЙ нет
                            WHEN
                                r1.id IS NULL
                                AND
                                r2.id IS NOT NULL
                                AND
                                r2.is_blocked = 0
                            THEN 'incoming'
                            
                            ELSE 'unknown'
                        END as relationship_type
                    FROM
                        users u
                        LEFT JOIN relationships r1 ON r1.user_id = ? AND r1.related_user_id = u.id
                        LEFT JOIN relationships r2 ON r2.user_id = u.id AND r2.related_user_id = ?
                        LEFT JOIN files f ON f.id = u.photo_id
                    WHERE
                        (r1.id IS NOT NULL OR r2.id IS NOT NULL)  -- Есть хотя бы одно отношение
                        AND
                        u.id != ?
                        AND
                        (r1.is_blocked = 0 OR r2.is_blocked = 0)  -- Хотя бы одно активно
                    ORDER BY
                        relationship_type ASC, u.firstname, u.lastname
                ";
                
                $allUsers = $this->db->fetchAll($sql, [$userId, $userId, $userId]);

                foreach ($allUsers as &$user) {
                    $user['photo'] = Helpers::fileUrl($user['photo'] ?? Helpers::imagePlaceholder('user'));
                }
                unset($user);
                
                $users = ['mutual' => [], 'outgoing' => [], 'incoming' => []];
                $seenIds = [];
                
                foreach ($allUsers as $user) {
                    if (in_array($user['id'], $seenIds) || $user['relationship_type'] === 'unknown') {
                        continue;
                    }
                    $seenIds[] = $user['id'];
                    $users[$user['relationship_type']][] = $user;
                }
                
                Helpers::jsonResponse(['success' => true, 'users' => $users]);
                
            } catch (Exception $e) {
                Helpers::errorResponse('Не удалось получить отношения', 404);
            }
        }
        
        /**
         * GET /relationships/get/{user_id}/{related_user_id} - получить отношение пользователя к другому пользователю
         */
        public function getRelationshipWithUser($userId, $relatedUserId) {
            Helpers::validateUserId($userId);
            Helpers::validateUserId($relatedUserId);

            $relationship = $this->db->fetchOne("
                    SELECT
                        *
                    FROM
                        relationships
                    WHERE
                        user_id = ?
                        AND
                        related_user_id = ?
                ",
                [$userId, $relatedUserId]
            );
            $relatedRelationship = $this->db->fetchOne("
                    SELECT
                        *
                    FROM
                        relationships
                    WHERE
                        user_id = ?
                        AND
                        related_user_id = ?
                ",
                [$relatedUserId, $userId]
            );

            // Пользователи подписаны друг на друга
            $userIsFollow = !empty($relationship) && !($relationship['is_blocked']);
            $relatedUserIsFollow = !empty($relatedRelationship) && !($relatedRelationship['is_blocked']);

            Helpers::jsonResponse([
                'success' => true,
                'isFollow' => $userIsFollow,
                'relatedIsFollow' => $relatedUserIsFollow,
                'relationship' => $relationship ?: null,
                'relatedRelationship' => $relatedRelationship ?: null
            ]);
        }
        
        /**
         * PUT /relationships/subscribe - создать отношение текущего пользователя к другому пользователю
         */
        public function subscribe() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $relatedUserId = $data['related_user_id'] ?? null;

            Helpers::validateUserId($relatedUserId);


            $this->upsertRelation($relatedUserId, ['is_blocked' => false]);


            // Уведомляем пользователя о подписке на него
            $relatedUserIsFollower = (bool) $this->db->fetchOne("
                    SELECT 1
                    FROM
                        relationships
                    WHERE
                        user_id = ?
                        AND
                        related_user_id = ?
                ",
                [$relatedUserId, $currentUserId]
            );
            $data = [
                'actor_id' => $currentUserId,
                'message' => $relatedUserIsFollower ? 'подписался на вас в ответ.' : 'подписался на вас.'
            ];
            $notificationId = Notifications::saveNotificationToDB($this->db, $relatedUserId, 'new_subscriber', json_encode($data));
            Redis::newNotification($relatedUserId, $notificationId);


            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * DELETE /relationships/unsubscribe - удалить отношение текущего пользователя к другому пользователю
         */
        public function unsubscribe() {
            $this->auth->check();
            $data = json_decode(file_get_contents('php://input'), true);

            $this->deleteRelation($data['related_user_id']);
            Helpers::jsonResponse(['success' => true]);
        }

        /**
         * PUT /relationships/block - создать отношение текущего пользователя к другому пользователю со значением is_blocked
         */
        public function block() {
            $this->auth->check();

            $data = json_decode(file_get_contents('php://input'), true);
            $isBlocked = $data['is_blocked'] ?? false;
            
            if ($isBlocked)
                $this->upsertRelation($data['related_user_id'], ['is_blocked' => $isBlocked]);
            else
                $this->deleteRelation($data['related_user_id']);

            Helpers::jsonResponse(['success' => true]);
        }

        /**
         * PUT /relationships/change-list - создать отношение текущего пользователя к другому пользователю со значением relationship_list_id
         */
        public function changeList() {
            $this->auth->check();

            $data = json_decode(file_get_contents('php://input'), true);
            $listId = $data['list_id'] ?? null;

            // if (!empty($listId))
            // {
            //     $this->upsertRelation($data['related_user_id'], ['relationship_list_id' => $listId]);
            //     Helpers::jsonResponse(['success' => true]);
            // } else {
            //     Helpers::errorResponse('Ошибка отношений', 409);
            // }
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
