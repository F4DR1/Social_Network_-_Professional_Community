<?php

    class Notifications {

        /**
         * Сохраняет уведомление в базе данных
         */
        public static function saveNotificationToDB($db, $userId, $typeText, $jsonData) {

            // Получаем id типа уведомления
            $row = $db->fetchOne("
                    SELECT
                        id
                    FROM
                        notification_types
                    WHERE
                        name = ?
                ",
                [$typeText]
            );
            $typeId = $row ? $row['id'] : null;
            if (empty($typeId)) {
                Helpers::errorResponse('Неизвестный тип уведомления', 400);
            }


            $db->query("
                    INSERT INTO notifications (user_id, type_id, data, is_read, created_at) 
                    VALUES (?, ?, ?, 0, NOW())
                ",
                [$userId, $typeId, $jsonData]
            );

            return $db->lastInsertId();
        }

        
    }
?>
