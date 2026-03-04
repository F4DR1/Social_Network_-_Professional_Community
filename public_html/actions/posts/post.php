<?php
    require_once '../../includes/session_start.php';
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $group_id = trim($_POST['group_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $content_raw = $_POST['content'] ?? '{}';

    // Проверка на пустые поля
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID пользователя']);
        exit;
    }
    if (empty($content_raw)) {
        echo json_encode(['success' => false, 'error' => 'Пустое содержимое поста']);
        exit;
    }


    $result = createPost($db, $user_id, $content_raw, $group_id);
    echo json_encode($result);
?>



<?php
    /**
     * Создает пост в таблице posts и связывает с группой (если передан group_id)
     * @param SQLite3 $db - подключение к БД
     * @param int $author_id - ID автора поста
     * @param string $content_raw - json строка с контентом
     * @param int|null $group_id - ID группы (опционально)
     * @return array ['success' => bool, 'message' => string|null, 'error' => string|null]
     */
    function createPost(SQLite3 $db, int $author_id, string $content_raw, ?int $group_id = null): array {
        $content = json_decode($content_raw, true) ?? [];
        if (json_last_error() !== JSON_ERROR_NONE || !isset($content['text'])) {
            return ['success' => false, 'message' => 'Ошибка в базе данных', 'error' => 'Неверный формат контента'];
        }

        // Валидация текста: 1-500 символов
        $text = trim($content['text'] ?? '');
        if (strlen($text) < 1 || strlen($text) > 500) {
            return ['success' => false, 'message' => 'Текст должен содержать от 1 до 500 символов', 'error' => 'Слишком большой текст'];
        }


        // Создаём пост одной транзакцией
        $db->exec('BEGIN TRANSACTION');
        try {
            // Создаем пост в таблице posts
            $stmt = $db->prepare('INSERT INTO posts (author_id, content, created_at) VALUES (?, ?, datetime("now"))');
            $stmt->bindValue(1, $author_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $content_raw, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            $post_id = $db->lastInsertRowID();

            if (!$post_id) {
                throw new Exception('Ошибка создания поста');
            }

            // Если передан group_id, создаем связь в group_posts
            if ($group_id) {
                $stmt = $db->prepare('INSERT INTO group_posts (group_id, post_id) VALUES (?, ?)');
                $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
                $stmt->bindValue(2, $post_id, SQLITE3_INTEGER);
                
                $result = $stmt->execute();
                if (!$result) {
                    throw new Exception('Ошибка привязки поста к группе');
                }
            }

            $db->exec('COMMIT');

            return ['success' => true];

        } catch (Exception $e) {
            // Если ошибка - ROLLBACK
            $db->exec('ROLLBACK');
            return ['success' => false, 'message' => 'Ошибка в базе данных', 'error' => 'Ошибка в базе данных'];
        }
    }
?>
