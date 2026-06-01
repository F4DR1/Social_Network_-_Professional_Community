<?php
    require_once 'core/Helpers.php';
    require_once 'ChatController.php';
    use Core\Redis;

    class MessageController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }



        /**
         * Получает сообщение по ID с данными автора.
         */
        private function getMessageById($currentUserId, $messageId) {
            $message = $this->db->fetchOne("
                    SELECT
                        m.*,
                        u.linkname AS author_linkname,
                        CONCAT(u.firstname, ' ', u.lastname) AS author_name,
                        f.file_path AS author_photo,
                        (SELECT COUNT(*) FROM chat_message_reads WHERE message_id = m.id) AS reads_count
                    FROM
                        chat_messages m
                        JOIN users u ON m.sender_id = u.id
                        LEFT JOIN files f ON u.photo_id = f.id
                    WHERE
                        m.id = ?
                ",
                [$messageId]
            );
            if ($message) {
                $message['author_photo'] = Helpers::fileUrl($message['author_photo'] ?? Helpers::imagePlaceholder('user'));
            }
            // Убираем счётчик, если пользователь не автор
            if ($message['sender_id'] != $currentUserId) {
                unset($message['reads_count']);
            }
            return $message;
        }


        


        /**
         * POST /messages/mark-read - отметить прочитанными чужие сообщения в чате
         */
        public function markRead() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $chatId = $data['chatId'] ?? null;
            $messageIds = $data['messageIds'] ?? null;  // Если передан массив конкретных сообщений


            if ($chatId) {
                // Отмечаем все непрочитанные чужие сообщения в чате как прочитанные
                Helpers::validateChatId($chatId);
                ChatController::checkIsMember($this->db, $chatId, $currentUserId);
                $this->db->query("
                        INSERT IGNORE INTO chat_message_reads (message_id, user_id, read_at)
                        SELECT
                            cm.id, ?, NOW()
                        FROM
                            chat_messages cm
                        WHERE
                            cm.chat_id = ?
                            AND
                            cm.sender_id != ?
                            AND
                            cm.id NOT IN (
                                SELECT
                                    mr.message_id
                                FROM
                                    chat_message_reads mr 
                                WHERE
                                    mr.user_id = ?
                                    AND
                                    mr.message_id = cm.id
                            )
                    ",
                    [$currentUserId, $chatId, $currentUserId, $currentUserId]
                );

            } elseif ($messageIds && is_array($messageIds)) {
                // Отмечаем конкретные чужие сообщения как прочитанные
                $placeholders = [];
                $params = [];
                foreach ($messageIds as $msgId) {
                    $placeholders[] = '?';
                    $params[] = $msgId;
                }

                $sql = "
                    INSERT IGNORE INTO chat_message_reads (message_id, user_id, read_at)
                    SELECT
                        cm.id, ?, NOW()
                    FROM
                        chat_messages cm
                    WHERE
                        cm.id IN (" . implode(',', $placeholders) . ")
                        AND
                        cm.sender_id != ?
                ";
                $params = array_merge([$currentUserId], $params, [$currentUserId]);

                $this->db->query($sql, $params);
            }
            
            Helpers::jsonResponse(['success' => true]);
        }

        /**
         * POST /messages/get - получить сообщения в чате
         */
        public function get() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            $data = json_decode(file_get_contents('php://input'), true);
            $chatId = $data['chatId'] ?? null;
            $lastMessageId = $data['lastMessageId'] ?? null;

            // Проверяем id
            Helpers::validateChatId($chatId);
            if (!empty($lastMessageId))
                Helpers::validateMessageId($lastMessageId);


            // Проверяем что есть доступ к чату
            ChatController::checkIsMember($this->db, $chatId, $currentUserId);


            // Основной запрос
            $sql = "
                SELECT
                    m.*,
                    u.linkname AS author_linkname,
                    CONCAT(u.firstname, ' ', u.lastname) AS author_name,
                    f.file_path AS author_photo,
                    (SELECT COUNT(*) FROM chat_message_reads WHERE message_id = m.id) AS reads_count
                FROM
                    chat_messages m
                    JOIN users u ON m.sender_id = u.id
                    LEFT JOIN files f ON f.id = u.photo_id
                WHERE
                    m.chat_id = ?
            ";
            $params = [$chatId];

            // Берём только сообщения позднее указанного (если указано)
            if (!empty($lastMessageId)) {
                $sql .= " AND m.id > ?";
                $params[] = $lastMessageId;
            }

            $sql .= " ORDER BY m.sent_at ASC";  // Берём по порядку публикации


            $messages = $this->db->fetchAll($sql, $params);


            // Ставим заглушки на фото пользователей, если у них нет фото
            foreach ($messages as &$message) {
                $message['author_photo'] = Helpers::fileUrl($message['author_photo'] ?? Helpers::imagePlaceholder('user'));

                // Счётчик прочтений показываем только владельцу сообщения
                if ($message['sender_id'] != $currentUserId) {
                    unset($message['reads_count']);
                }
            }

            Helpers::jsonResponse(['success' => true, 'messages' => $messages]);
        }

        /**
         * POST /messages/send - отправить сообщение
         */
        public function send() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            $data = json_decode(file_get_contents('php://input'), true);
            $chatId = $data['chatId'] ?? null;
            $dataText = $data['text'] ?? null;
            $text = empty($dataText) ? null : trim($dataText);
            
            $relatedUserId = $data['userId'] ?? null;

            
            // Проверяем данные
            if (empty($text))
                Helpers::errorResponse('Нужно указать текст для отправки в чат', 400);


            // Получаем id чата
            if (empty($chatId)) {
                // Если chatId не указан, но указан relatedUserId – ищем/создаём приватный чат
                
                if (empty($relatedUserId)) {
                    Helpers::errorResponse('Необходимо указать chatId или relatedUserId', 400);
                }

                Helpers::validateUserId($relatedUserId);
                
                // Нельзя создавать чат с самим собой
                if ($relatedUserId == $currentUserId) {
                    Helpers::errorResponse('Нельзя создать чат с самим собой', 400);
                }

                // Ищем существующий приватный чат
                $chatId = ChatController::getPrivateChatId($this->db, $currentUserId, $relatedUserId);

                // Если не найден – создаём
                if (!$chatId) {
                    $chatId = ChatController::createPrivateChat($this->db, $currentUserId, $relatedUserId);
                }

            } else {
                Helpers::validateChatId($chatId);
            }


            // Проверяем что есть доступ к чату
            ChatController::checkIsMember($this->db, $chatId, $currentUserId);


            // Вставляем сообщение
            $this->db->query("
                    INSERT INTO chat_messages (chat_id, sender_id, text, sent_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ",
                [$chatId, $currentUserId, $text]
            );
            $messageId = $this->db->lastInsertId();
            
            Redis::newMessage($chatId, $currentUserId, $messageId);

            // Получаем полные данные сообщения
            $message = $this->getMessageById($currentUserId, $messageId);
            
            Helpers::jsonResponse(['success' => true, 'message' => $message]);
        }
    }
?>
