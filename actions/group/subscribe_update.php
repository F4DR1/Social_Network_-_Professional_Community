<?php
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $group_id = trim($_POST['group_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $action = $_POST['action'] ?? '';
    $value = $_POST['value'] ?? '';

    // Проверка на пустые поля
    if (empty($group_id) || empty($user_id) || empty($action) || empty($value)) {
        echo json_encode(['success' => false, 'error' => 'Указаны не все данные']);
        exit;
    }


    $success = false;
    switch ($action) {
        case 'Subscribe':
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                echo json_encode(['success' => false, 'error' => 'Неверное значение boolean']);
                exit;
            }
            
            $success = (bool)$value ? insertRecord($db, $group_id, $user_id) : deleteRecord($db, $group_id, $user_id);
            break;
        
        default:
            $success = false;
            break;
    }
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось выполнить действие']);
    }
    exit;
?>



<?php
    function insertRecord($db, $group_id, $user_id) {
        if (recordExists($db, $group_id, $user_id)) return false;

        $stmt = $db->prepare('INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, datetime("now"))');
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);

        $result = $stmt->execute();
        return $result !== false && $db->changes() > 0;
    }

    function deleteRecord($db, $group_id, $user_id) {
        if (!recordExists($db, $group_id, $user_id)) return true;

        $stmt = $db->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);

        $result = $stmt->execute();
        return $result !== false && $db->changes() > 0;
    }



    function recordExists($db, $group_id, $user_id) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0];
        
        return $count > 0;
    }
?>
