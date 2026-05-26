<?php
    require_once 'core/Helpers.php';
    require_once 'GroupController.php';
    use Core\Redis;

    class ChatController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }


        
        /**
         * Проверяет является ли пользователь участником чата
         */
        public static function checkIsMember($db, $chatId, $currentUserId) {
            // Проверка членства в чате
            $isMember = $db->fetchOne("
                    SELECT 1
                    FROM
                        chat_members
                    WHERE
                        chat_id = ?
                        AND
                        user_id = ?
                ",
                [$chatId, $currentUserId]
            );
            if (!$isMember) {
                Helpers::errorResponse('Вы не являетесь участником этого чата', 403);
            }
        }



        /**
         * Поиск ID приватного чата между двумя пользователями.
         * Возвращает ID или null.
         */
        public static function getPrivateChatId($db, $userId1, $userId2) {
            $row = $db->fetchOne("
                    SELECT
                        c.id
                    FROM
                        chats c
                        INNER JOIN chat_members cm ON c.id = cm.chat_id
                    WHERE
                        c.is_private = 1
                        AND
                        cm.user_id IN (?, ?)
                    GROUP BY
                        c.id
                    HAVING
                        COUNT(DISTINCT cm.user_id) = 2
                        AND
                        COUNT(*) = 2
                ",
                [$userId1, $userId2]
            );
            return $row ? (int)$row['id'] : null;
        }

        /**
         * Создаёт приватный чат между двумя пользователями.
         * Возвращает ID нового чата.
         */
        public static function createPrivateChat($db, $userId1, $userId2) {
            $db->beginTransaction();
            try {
                $db->query("
                        INSERT INTO chats (is_private, created_at)
                        VALUES (1, NOW())
                    "
                );
                $chatId = $db->lastInsertId();

                $db->query("
                        INSERT INTO chat_members (chat_id, user_id, joined_at)
                        VALUES (?, ?, NOW()), (?, ?, NOW())
                    ",
                    [$chatId, $userId1, $chatId, $userId2]
                );

                $db->commit();
                return $chatId;

            } catch (Exception $e) {
                $db->rollBack();
                Helpers::errorResponse('Ошибка создания чата: ' . $e->getMessage(), 500);
            }
        }
        




        /**
         * GET /chats/get/id/user/{user_id} - получить id приватного чата с указанным пользователем
         */
        public function getIdByUser($relatedUserId) {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            Helpers::validateUserId($relatedUserId);

            $row = $this->db->fetchOne("
                    SELECT
                        c.id
                    FROM
                        chats c
                        INNER JOIN chat_members cm ON c.id = cm.chat_id
                    WHERE
                        c.is_private = 1
                        AND
                        cm.user_id IN (?, ?)
                    GROUP BY
                        c.id
                    HAVING
                        COUNT(DISTINCT cm.user_id) = 2
                        AND
                        COUNT(*) = 2
                ",
                [$currentUserId, $relatedUserId]
            );

            $chatId = $row ? (int) $row['id'] : null;
            
            Helpers::jsonResponse(['success' => true, 'chatId' => $chatId]);
        }

        /**
         * GET /chats/get/id/group/{group_id} - получить id чата по id группы
         */
        public function getIdByGroup($groupId) {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            Helpers::validateGroupId($groupId);
            GroupController::checkIsMember($this->db, $groupId, $currentUserId);

            $row = $this->db->fetchOne("
                    SELECT
                        c.id
                    FROM
                        chats c
                        INNER JOIN groups g ON g.chat_id = c.id
                    WHERE
                        g.id = ?
                ",
                [$groupId]
            );

            $chatId = $row ? (int) $row['id'] : null;
            
            Helpers::jsonResponse(['success' => true, 'chatId' => $chatId]);
        }

        /**
         * POST /chats/get/info - получить информацию о чате по его id
         */
        public function getInfo() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            $data = json_decode(file_get_contents('php://input'), true);

            $type = $data['type'] ?? null;
            $chatId = $data['chatId'] ?? null;
            $userId = $data['userId'] ?? null;
            $groupId = $data['groupId'] ?? null;

            switch ($type) {
                case 'chat':
                    Helpers::validateChatId($chatId);
                    $this->checkIsMember($this->db, $chatId, $currentUserId);
                    $sql = "
                        SELECT
                            CASE
                                WHEN c.is_private
                                    THEN CONCAT(other_user.firstname, ' ', other_user.lastname)
                                    ELSE c.title
                            END AS chat_title,
                            CASE
                                WHEN c.is_private
                                    THEN other_user_photo.file_path
                                    ELSE chat_photo.file_path
                            END AS chat_photo,
                            CASE
                                WHEN c.is_private
                                    THEN NULL
                                    ELSE (SELECT COUNT(*) FROM chat_members WHERE chat_id = c.id)
                            END AS chat_members_count,
                            c.is_private
                        FROM
                            chats c
                            LEFT JOIN files chat_photo ON c.photo_id = chat_photo.id

                            -- Собеседник для приватного чата
                            LEFT JOIN chat_members cm ON cm.chat_id = c.id AND cm.user_id != ? AND c.is_private = 1
                            LEFT JOIN users other_user ON other_user.id = cm.user_id
                            LEFT JOIN files other_user_photo ON other_user_photo.id = other_user.photo_id
                        WHERE c.id = ?
                    ";
                    $params = [$currentUserId, $chatId];
                    break;

                case 'user':
                    Helpers::validateUserId($userId);
                    $sql = "
                        SELECT
                            CONCAT(u.firstname, ' ', u.lastname) AS chat_title,
                            f.file_path AS chat_photo,
                            NULL AS chat_members_count,
                            1 AS is_private
                        FROM
                            users u
                            LEFT JOIN files f ON f.id = u.photo_id
                        WHERE
                            u.id = ?
                    ";
                    $params = [$userId];
                    break;

                case 'group':
                    Helpers::validateGroupId($groupId);
                    GroupController::checkIsMember($this->db, $groupId, $currentUserId);
                    $sql = "
                        SELECT
                            c.title AS chat_title,
                            f.file_path AS chat_photo,
                            (SELECT COUNT(*) FROM chat_members WHERE chat_id = c.id) AS chat_members_count,
                            c.is_private
                        FROM
                            groups g
                            INNER JOIN chats c ON c.id = g.chat_id
                            LEFT JOIN files f ON f.id = c.photo_id
                        WHERE
                            g.id = ?
                    ";
                    $params = [$groupId];
                    break;
                
                default:
                    Helpers::errorResponse('Не указан тип для поиска информации о чате', 400);
                    break;
            }
            $chat = $this->db->fetchOne($sql, $params);
            
            // Проставляем полные URL для фото
            if ($chat)
                $chat['chat_photo'] = Helpers::fileUrl($chat['chat_photo'] ?? Helpers::imagePlaceholder(($chat['is_private'] ? 'user' : 'chat')));
            
            Helpers::jsonResponse(['success' => true, 'chat' => $chat]);
        }
        
        /**
         * GET /messages/get/unread-count - получить количество непрочитанных чатов
         */
        public function getUnreadCount() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $row = $this->db->fetchOne("
                    SELECT
                        COUNT(DISTINCT cm.chat_id) AS cnt
                    FROM
                        chat_messages cm
                        INNER JOIN chat_members cms ON cm.chat_id = cms.chat_id AND cms.user_id = ?
                    WHERE
                        cm.sender_id != ?
                        AND
                        NOT EXISTS (
                            SELECT 1
                            FROM
                                chat_message_reads mr 
                            WHERE
                                mr.message_id = cm.id
                                AND
                                mr.user_id = ?
                        )
                ",
                [$currentUserId, $currentUserId, $currentUserId]
            );

            $count = $row ? (int) $row['cnt'] : 0;
            
            Helpers::jsonResponse(['success' => true, 'count' => $count]);
        }

        /**
         * GET /chats/get - получить все чаты пользователя
         */
        public function get() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];


            $chats = $this->db->fetchAll("
                    SELECT
                        c.id,
                        CASE
                            WHEN c.is_private
                                THEN CONCAT(other_user.firstname, ' ', other_user.lastname)
                                ELSE c.title
                        END AS chat_title,
                        CASE
                            WHEN c.is_private
                                THEN other_user_photo.file_path
                                ELSE chat_photo.file_path
                        END AS chat_photo,
                        c.is_private,
                        COALESCE(unread.cnt, 0) AS unread_count,
                        last_msg.text AS last_message_text,
                        last_msg.sent_at AS last_message_time,
                        last_user.id AS last_message_author_id,
                        CONCAT(last_user.firstname, ' ', last_user.lastname) AS last_message_author_name,
                        last_user_photo.file_path AS last_message_author_photo
                    FROM
                        chats c
                        INNER JOIN chat_members cm ON c.id = cm.chat_id AND cm.user_id = ?
                        LEFT JOIN (
                            SELECT chat_id, COUNT(*) AS cnt
                            FROM chat_messages cm2
                            WHERE NOT EXISTS (
                                SELECT 1 FROM chat_message_reads mr 
                                WHERE mr.message_id = cm2.id AND mr.user_id = ?
                            )
                            GROUP BY chat_id
                        ) unread ON unread.chat_id = c.id
                        LEFT JOIN (
                            SELECT cm3.chat_id, cm3.text, cm3.sent_at, cm3.sender_id
                            FROM chat_messages cm3
                            INNER JOIN (
                                SELECT chat_id, MAX(id) AS max_id
                                FROM chat_messages
                                GROUP BY chat_id
                            ) last_ids ON cm3.chat_id = last_ids.chat_id AND cm3.id = last_ids.max_id
                        ) last_msg ON last_msg.chat_id = c.id
                        LEFT JOIN users last_user ON last_user.id = last_msg.sender_id
                        LEFT JOIN files last_user_photo ON last_user.photo_id = last_user_photo.id
                        LEFT JOIN files chat_photo ON c.photo_id = chat_photo.id
                        -- Данные собеседника для приватного чата
                        LEFT JOIN chat_members cm2 ON c.id = cm2.chat_id AND cm2.user_id != ? AND c.is_private = 1
                        LEFT JOIN users other_user ON other_user.id = cm2.user_id
                        LEFT JOIN files other_user_photo ON other_user.photo_id = other_user_photo.id
                    ORDER BY
                        last_msg.sent_at DESC
                ",
                [$currentUserId, $currentUserId, $currentUserId]
            );

            foreach ($chats as &$chat) {
                // Проставляем полные URL для фото
                $chat['chat_photo'] = Helpers::fileUrl($chat['chat_photo'] ?? Helpers::imagePlaceholder(($chat['is_private'] ? 'user' : 'chat')));
                $chat['last_message_author_photo'] = Helpers::fileUrl($chat['last_message_author_photo'] ?? Helpers::imagePlaceholder('user'));
            }
            unset($chat);
            
            
            Helpers::jsonResponse(['success' => true, 'chats' => $chats]);
        }

        /**
         * GET /chats/members/get/{chat_id}/ids - получить id всех участников чата
         */
        public function getMembersIds($chatId) {

            // Авторизация через токен из куки или со стороны WS
            if (!Helpers::isInternalRequest()) {
                $this->auth->check();
                $currentUserId = $this->auth->getCurrentUser()['id'];
                
                // Проверяем, что текущий пользователь является участником чата
                $this->checkIsMember($this->db, $chatId, $currentUserId);
            }

            Helpers::validateChatId($chatId);


            // Получаем всех участников чата (кроме самого запрашивающего)
            $members = $this->db->fetchAll("
                    SELECT
                        user_id
                    FROM
                        chat_members
                    WHERE
                        chat_id = ?
                ",
                [$chatId]
            );

            // Извлекаем только ID и преобразуем в целые числа
            $chatMembersIds = array_map('intval', array_column($members, 'user_id'));

            Helpers::jsonResponse(['success' => true, 'chatMembersIds' => $chatMembersIds]);
        }
    }
?>
