<?php
    require_once 'core/Helpers.php';

    class NotificationController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        


        /**
         * GET /notifications/get/unread-count - получить количество непрочитанных уведомлений
         */
        public function getUnreadCount() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $countRow = $this->db->fetchOne("
                    SELECT
                        COUNT(*) AS unread_count
                    FROM
                        notifications
                    WHERE
                        is_read = 0
                        AND
                        user_id = ?
                ",
                [$currentUserId]
            );

            $unreadCount = $countRow ? (int) $countRow['unread_count'] : 0;
            
            Helpers::jsonResponse(['success' => true, 'count' => $unreadCount]);
        }

        /**
         * POST /notifications/get - получить все уведомления
         */
        public function get() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $notifications = $this->db->fetchAll("
                    SELECT
                        n.*,
                        t.name AS type
                    FROM
                        notifications n
                        INNER JOIN notification_types t ON n.type_id = t.id
                    WHERE
                        n.user_id = ?
                    ORDER BY
                        n.is_read ASC,
                        n.created_at DESC
                ",
                [$currentUserId]
            );
            
            Helpers::jsonResponse(['success' => true, 'notifications' => $notifications]);
        }

        /**
         * POST /notifications/mark-read - отметить уведомления прочитаными
         */
        public function markRead() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $notificationIds = $data['messageIds'] ?? null;  // Массив конкретных уведомлений
            
            if (!empty($notificationIds) && is_array($notificationIds)) {
                // Отмечаем конкретные уведомления
                $ids = array_filter(array_map('intval', $notificationIds), function($id) {
                    // Фильтруем только целые положительные ID
                    return $id > 0;
                });
                if (empty($ids)) {
                    Helpers::errorResponse('Не указаны корректные ID уведомлений', 400);
                }

                // Генерируем плейсхолдеры
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $params = $ids;
                $params[] = $currentUserId;  // Добавляем currentUserId в конец для проверки владельца

                $this->db->query("
                        UPDATE
                            notifications
                        SET
                            is_read = 1
                        WHERE
                            id IN ($placeholders)
                            AND
                            user_id = ?
                            AND
                            is_read = 0
                    ",
                    $params
                );

            } else {
                // Отмечаем все непрочитанные уведомления пользователя
                $this->db->query("
                        UPDATE
                            notifications
                        SET
                            is_read = 1
                        WHERE
                            user_id = ?
                            AND
                            is_read = 0
                    ",
                    [$currentUserId]
                );
            }
            
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
