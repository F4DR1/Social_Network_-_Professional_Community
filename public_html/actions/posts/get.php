<?php
    require_once '../../includes/session_start.php';
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $group_id = trim($_POST['group_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');

    // Проверка на пустые поля
    if (empty($group_id) && empty($user_id)) {  // В будущем можно будет получить посты ИЛИ группы, ИЛИ пользователя (так что на это рассчёт в проверке)
        echo json_encode(['success' => false, 'error' => 'Не указан ID']);
        exit;
    }


    try {
        $posts = getGroupPosts($db, $group_id);
        echo json_encode(['success' => true, 'posts' => $posts]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка загрузки постов', 'error' => 'Ошибка в базе данных']);
        exit;
    }
?>



<?php
    /**
     * Получает все посты группы с данными авторов и группы
     * @param SQLite3 $db соединение с БД
     * @param int $group_id ID группы
     * @return array массив постов с данными авторов и группы
     */
    function getGroupPosts(SQLite3 $db, int $group_id): array {
        $query = "
            SELECT 
                p.id as post_id,
                p.content,
                p.created_at as post_created_at,
                u.id as user_id,
                u.linkname as user_linkname,
                u.firstname,
                u.lastname,
                u.photo as user_photo,
                g.id as group_id,
                g.linkname as group_linkname,
                g.name as group_name,
                g.photo as group_photo
            FROM posts p
            INNER JOIN group_posts gp ON p.id = gp.post_id
            INNER JOIN users u ON p.author_id = u.id
            INNER JOIN groups g ON gp.group_id = g.id
            WHERE gp.group_id = ?
            ORDER BY p.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Ошибка подготовки запроса: ' . $db->lastErrorMsg());
        }
        
        // Привязываем group_id через bindValue
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception('Ошибка выполнения запроса: ' . $db->lastErrorMsg());
        }
        
        $posts = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $posts[] = [
                'id' => (int)$row['post_id'],
                // 'content' => $row['content'],
                'content' => json_decode($row['content'], true) ?? ['text' => ''],
                'created_at' => $row['post_created_at'],
                'author' => [
                    'id' => (int)$row['user_id'],
                    'linkname' => $row['user_linkname'],
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'photo' => $row['user_photo']
                ],
                'group' => [
                    'id' => (int)$row['group_id'],
                    'linkname' => $row['group_linkname'],
                    'name' => $row['group_name'],
                    'photo' => $row['group_photo']
                ]
            ];
        }
        
        $result->finalize();
        $stmt->close();
        
        return $posts;
    }
?>

