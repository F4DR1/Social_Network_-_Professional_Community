<?php
    require_once 'session_start.php';
    require_once __DIR__ . '/../database/db_config.php';  // Абсолютный путь

    
    global $db, $current_user_id, $current_user;

    
    // Данные текущего пользователя
    $current_user_id = $_SESSION['user_id'] ?? null;
    $current_user = null;

    if ($current_user_id) {
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $current_user = $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }
?>
