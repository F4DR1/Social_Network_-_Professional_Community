<?php
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');
    
    // Получаем данные из POST
    $group_id = trim($_POST['group_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');

    // Проверка на пустые поля
    if (empty($group_id) || empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'Указаны не все данные']);
        exit;
    }
    

    echo json_encode([
        'success' => true,
        'is_subscribe' => isGroupMember($db, $group_id, $user_id),
        'is_owner' => isGroupOwner($db, $group_id, $user_id)
    ]);
    exit;
?>



<?php
    function isGroupMember($db, $group_id, $user_id) {
        $stmt = $db->prepare("
            SELECT 1 
            FROM group_members 
            WHERE group_id = ? 
            AND user_id = ?
            LIMIT 1
        ");
        
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        return $result->fetchArray() !== false;
    }

    function isGroupOwner($db, $group_id, $user_id) {
        $stmt = $db->prepare("
            SELECT 1 
            FROM group_members gm
            JOIN group_roles gr ON gm.role_id = gr.id
            WHERE gm.group_id = ? 
            AND gm.user_id = ?
            AND gr.name = 'owner'
            LIMIT 1
        ");
        
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        return $result->fetchArray() !== false;
    }
?>
