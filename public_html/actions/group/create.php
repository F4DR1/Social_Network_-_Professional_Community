<?php
    require_once '../../includes/session_start.php';
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $user_id = trim($_POST['user_id'] ?? '');
    $group_name = $_POST['name'] ?? '';

    // Проверка на пустые поля
    if (empty($group_name)) {
        echo json_encode(['success' => false, 'message' => 'Введите название для группы.']);
        exit;
    }
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID пользователя, который создаёт группу']);
        exit;
    }


    // Проверка id пользователя
    $stmt = $db->prepare('SELECT 1 FROM users WHERE id = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result->fetchArray(SQLITE3_ASSOC) === false) {
        echo json_encode(['success' => false, 'error' => 'Такого пользователя нет в системе!']);
        exit;
    }


    
    // Получение id роли создателя
    $stmt = $db->prepare('SELECT id FROM group_roles WHERE name = ?');
    $stmt->bindValue(1, 'owner', SQLITE3_TEXT);
    $result = $stmt->execute();
    $role = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$role) {
        echo json_encode(['success' => false, 'error' => 'Роль "owner" не найдена в системе!']);
        exit;
    }
    
    $owner_role_id = $role['id'];



    // Создаем группу одной транзакцией
    $db->exec('BEGIN TRANSACTION');
    try {
        $stmt = $db->prepare('INSERT INTO groups (name, created_at) VALUES (?, datetime("now"))');
        $stmt->bindValue(1, $group_name, SQLITE3_TEXT);
        $result = $stmt->execute();

        if (!$result) {
            throw new Exception('Не удалось внести запись группы в таблицу');
        }

        $group_id = $db->lastInsertRowID();


        $stmt = $db->prepare('INSERT INTO group_members (group_id, user_id, role_id, joined_at) VALUES (?, ?, ?, datetime("now"))');
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(3, $owner_role_id, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if (!$result) {
            throw new Exception('Не удалось внести запись пользователя в таблицу');
        }

        $db->exec('COMMIT');
        
        echo json_encode(['success' => true, 'group_id' => $group_id]);
        
    } catch (Exception $e) {
        // Если ошибка - ROLLBACK
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false, 
            'message' => 'Ошибка при создании группы. Попробуйте позже', 
            'error' => $e->getMessage()
        ]);
    }
    exit;
?>
