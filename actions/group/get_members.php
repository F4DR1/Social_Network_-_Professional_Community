<?php
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');
    
    // Получаем данные из POST
    $group_id = trim($_POST['group_id'] ?? '');

    // Проверка на пустые поля
    if (empty($group_id)) {
        echo json_encode(['success' => false, 'error' => 'Указаны не все данные']);
        exit;
    }



    $members = [
        'users' => [],
        'count' => 0
    ];
    
    // Получаем общее количество участников
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM group_members 
        WHERE group_id = ?
    ");
    $countStmt->bindValue(1, $group_id, SQLITE3_INTEGER);
    $countResult = $countStmt->execute();
    $countRow = $countResult->fetchArray(SQLITE3_ASSOC);
    $members['count'] = $countRow['total'] ?? 0;
    
    // Получаем список участников с данными пользователей
    if ($members['count'] > 0) {
        $membersStmt = $db->prepare("
            SELECT 
                u.id,
                u.firstname,
                u.lastname,
                u.photo,
                u.linkname,
                gm.role_id,
                gm.joined_at
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.joined_at DESC
        ");
        $membersStmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $membersResult = $membersStmt->execute();
        
        while ($row = $membersResult->fetchArray(SQLITE3_ASSOC)) {
            $members['users'][] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'members' => $members
    ]);
    exit;
?>
