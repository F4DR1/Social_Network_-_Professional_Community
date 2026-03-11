<?php
    require_once '../../includes/session_start.php';
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $post_id = trim($_POST['post_id'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');

    // Проверка на пустые поля
    if (empty($post_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID поста']);
        exit;
    }
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID пользователя']);
        exit;
    }


    try {
        if (empty($group_id)) {
            // Тут будет удаление постов пользователей (заглушка)
            echo json_encode(['success' => false, 'error' => 'Удаление личных постов пока не реализовано']);
            exit;
        } else {
            // Удаление поста группы
            $result = deleteGroupPost($db, $post_id, $group_id, $user_id);
            echo json_encode($result);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
        exit;
    }
?>



<?php
    /**
     * Удаляет пост группы из базы данных
     * Сначала удаляется из group_posts, потом из posts
     * @param SQLite3 $db соединение с БД
     * @param int $post_id ID поста
     * @param int $group_id ID группы  
     * @param int $user_id ID пользователя (для проверки прав)
     * @return array результат операции
     * @throws Exception при ошибках
     */
    function deleteGroupPost(SQLite3 $db, int $post_id, int $group_id, int $user_id): array {
        // Начинаем транзакцию
        $db->exec('BEGIN TRANSACTION');
        
        try {
            // 1. Проверяем существование поста в группе
            $checkQuery = "
                SELECT p.id, p.author_id, gp.group_id 
                FROM posts p
                INNER JOIN group_posts gp ON p.id = gp.post_id
                WHERE p.id = ? AND gp.group_id = ?
            ";
            
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindValue(1, $post_id, SQLITE3_INTEGER);
            $checkStmt->bindValue(2, $group_id, SQLITE3_INTEGER);
            $checkResult = $checkStmt->execute();
            
            $post = $checkResult->fetchArray(SQLITE3_ASSOC);
            $checkStmt->close();
            
            if (!$post) {
                $db->exec('ROLLBACK');
                return ['success' => false, 'error' => 'Пост не найден в указанной группе'];
            }
            
            // 2. Удаляем связь из group_posts (явно!)
            $groupPostQuery = "DELETE FROM group_posts WHERE group_id = ? AND post_id = ?";
            $groupPostStmt = $db->prepare($groupPostQuery);
            $groupPostStmt->bindValue(1, $group_id, SQLITE3_INTEGER);
            $groupPostStmt->bindValue(2, $post_id, SQLITE3_INTEGER);
            
            $groupPostResult = $groupPostStmt->execute();
            if (!$groupPostResult) {
                throw new Exception('Ошибка удаления из group_posts: ' . $db->lastErrorMsg());
            }
            
            $groupPostDeleted = $db->changes();
            $groupPostStmt->close();
            
            // 3. Удаляем сам пост из posts
            $postQuery = "DELETE FROM posts WHERE id = ?";
            $postStmt = $db->prepare($postQuery);
            $postStmt->bindValue(1, $post_id, SQLITE3_INTEGER);
            
            $postResult = $postStmt->execute();
            if (!$postResult) {
                throw new Exception('Ошибка удаления из posts: ' . $db->lastErrorMsg());
            }
            
            $postDeleted = $db->changes();
            $postStmt->close();
            
            // 4. Подтверждаем транзакцию
            $db->exec('COMMIT');
            
            return [
                'success' => true, 
                'message' => 'Пост группы успешно удален',
                'deleted_post_id' => $post_id,
                'deleted_from_group_posts' => $groupPostDeleted,
                'deleted_from_posts' => $postDeleted
            ];
            
        } catch (Exception $e) {
            // Откатываем транзакцию при любой ошибке
            $db->exec('ROLLBACK');
            throw $e;
        }
    }
?>
