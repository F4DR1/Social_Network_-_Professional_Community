<?php
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $user_id = trim($_POST['user_id'] ?? '');
    $related_user_id = trim($_POST['related_user_id'] ?? '');
    $action = $_POST['action'] ?? '';
    $value = $_POST['value'] ?? '';

    // Проверка на пустые поля
    if (empty($user_id) || empty($related_user_id) || empty($action) || empty($value)) {
        echo json_encode(['success' => false, 'error' => 'Указаны не все данные']);
        exit;
    }

    if ($user_id === $related_user_id) {
        echo json_encode(['success' => false, 'message' => 'Такое действие нельзя выполнить с самим собой']);
        exit;
    }


    $success = false;
    switch ($action) {
        case 'Follow':
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                echo json_encode(['success' => false, 'error' => 'Неверное значение boolean']);
                exit;
            }
            
            $success = (bool)$value ? insertRecord($db, $user_id, $related_user_id) : deleteRecord($db, $user_id, $related_user_id);
            break;
        
        case 'Block':
            $success = updateRecord($db, "is_blocked = $value", $user_id, $related_user_id);
            break;
        
        case 'ChangeList':
            $success = updateRecord($db, "relationship_list_id = $value", $user_id, $related_user_id);
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
    function insertRecord($db, $user_id, $related_user_id) {
        if (recordExists($db, $user_id, $related_user_id)) {
            $record = recordGet($db, $user_id, $related_user_id);
            if (!$record['is_blocked']) {
                return false;
            } else {
                return updateRecord($db, "is_blocked = false", $user_id, $related_user_id);
            }
        }

        $stmt = $db->prepare("INSERT INTO relationships (user_id, related_user_id) VALUES (?, ?)");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $related_user_id, SQLITE3_INTEGER);

        $result = $stmt->execute();
        return $result !== false && $db->changes() > 0;
    }

    function deleteRecord($db, $user_id, $related_user_id) {
        if (!recordExists($db, $user_id, $related_user_id)) return true;

        $stmt = $db->prepare("DELETE FROM relationships WHERE user_id = ? AND related_user_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $related_user_id, SQLITE3_INTEGER);

        $result = $stmt->execute();
        return $result !== false && $db->changes() > 0;
    }
    
    function updateRecord($db, $set, $user_id, $related_user_id) {
        if (!recordExists($db, $user_id, $related_user_id)) return false;
        
        $stmt = $db->prepare("UPDATE relationships SET $set WHERE user_id = ? AND related_user_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $related_user_id, SQLITE3_INTEGER);

        $result = $stmt->execute();
        return $result !== false && $db->changes() > 0;
    }



    function recordExists($db, $user_id, $related_user_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM relationships WHERE user_id = ? AND related_user_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $related_user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0];
        
        return $count > 0;
    }

    function recordGet($db, $user_id, $related_user_id) {
        $stmt = $db->prepare("SELECT * FROM relationships WHERE user_id = ? AND related_user_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $related_user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        return $row;
    }
?>
