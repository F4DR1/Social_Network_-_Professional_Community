<?php
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');
    
    // Получаем данные из POST
    $current_user_id = trim($_POST['current_user_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');

    // Проверка на пустые поля
    if (empty($current_user_id) || empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'Указаны не все данные']);
        exit;
    }

    // Записи
    $following_relationship = recordGet($db, $current_user_id, $user_id);
    $follower_relationship = recordGet($db, $user_id, $current_user_id);


    $is_following_record = !empty($following_relationship);
    $following_is_blocked = !empty($following_relationship) && $following_relationship['is_blocked'] === true;

    $is_follower_record = !empty($follower_relationship);
    $follower_is_blocked = !empty($follower_relationship) && $follower_relationship['is_blocked'] === true;

    
    echo json_encode([
        'success' => true,
        'is_following_record' => $is_following_record, 'following_is_blocked' => $following_is_blocked,
        'is_follower_record' => $is_follower_record, 'follower_is_blocked' => $follower_is_blocked,
    ]);
    exit;
?>



<?php
    function recordGet($db, $user_id, $related_user_id) {
        $stmt = $db->prepare("SELECT * FROM relationships WHERE user_id = ? AND related_user_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $related_user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        return $row;
    }
?>
