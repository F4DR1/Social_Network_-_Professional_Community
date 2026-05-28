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
    }
?>
