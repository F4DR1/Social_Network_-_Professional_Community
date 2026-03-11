<?php
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $group_id = trim($_POST['group_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $action = $_POST['action'] ?? '';
    $value = json_decode($_POST['value'] ?? '{}', true) ?? [];

    // Проверка на пустые поля
    if (empty($group_id) || empty($user_id) || empty($action) || empty($value)) {
        echo json_encode(['success' => false, 'error' => 'Указаны не все данные']);
        exit;
    }


    $success = false;
    switch ($action) {
        case 'Base':
            $group = getGroup($db, $group_id);
            if (isset($group)) {
                // Проверка изменился ли linkname
                $group_linkname = $group['linkname'] ?? '';
                $new_linkname = $value['linkname'];
                if ($new_linkname === 'group' . $group_id) {
                    $value['linkname'] = '';
                    $new_linkname = '';
                }
                if ($group_linkname !== $new_linkname && !empty($new_linkname)) {
                    // Проверяем валидность linkname
                    $result = isValidLinkname($new_linkname);
                    if (!$result['is_valid']) {
                        echo json_encode(['success' => false, 'message' => 'Адрес: ' . $result['error']]);
                        exit;
                    }
                    // Проверяем доступность linkname
                    if (!isLinknameAvailable($db, $new_linkname)) {
                        echo json_encode(['success' => false, 'message' => 'Адрес уже занят']);
                        exit;
                    }
                }
                $success = updateBaseInfo($db, $group_id, $value);
            }
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
    /**
     * Проверяет корректность linkname
     * 
     * @param string $linkname проверяемая строка
     * @return bool true если корректно
     */
    function isValidLinkname($linkname) {
        // Проверка длины
        if (strlen($linkname) < 5) {
            return ['is_valid' => false, 'error' => 'Минимум 5 символов'];
        }
        
        // Регулярное выражение:
        // ^              - начало строки
        // [a-zA-Z]       - первая буква (только английская)
        // [a-zA-Z0-9_-]* - остальные символы (буквы, цифры, -, _)
        // $              - конец строки
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $linkname)) {
            return ['is_valid' => false, 'error' => 'Только английские буквы, цифры, дефис и подчеркивание, без числа в начале'];
        }

        // Не начинается с user или group
        if (preg_match('/^(user|group)\d/', $linkname)) {
            return ['is_valid' => false, 'error' => 'Нельзя начинать с "user" или "group" + цифры'];
        }

        return ['is_valid' => true, 'error' => null];
    }

    function isLinknameAvailable($db, $linkname) {
        $stmt = $db->prepare("
            SELECT id 
            FROM groups 
            WHERE linkname = ? 
            LIMIT 1
        ");
        
        $stmt->bindValue(1, $linkname, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        return $result->fetchArray() === false;
    }
    
    function getGroup($db, $group_id) {
        $stmt = $db->prepare("
            SELECT * 
            FROM groups 
            WHERE id = ?
        ");
        
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        return $row ?: null;
    }



    function updateBaseInfo($db, $group_id, $value) {
        if (!isset($value['name']) || !isset($value['linkname']) || empty($value['name'])) {
            return false;
        }
        
        // Экранируем данные
        $name = trim($value['name']);
        $linkname = empty($value['linkname']) ? null : trim($value['linkname']);
        
        // Проверяем существование группы
        $checkStmt = $db->prepare("SELECT id FROM groups WHERE id = ?");
        $checkStmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $checkResult = $checkStmt->execute();
        
        if (!$checkResult->fetchArray()) return false;  // Группа не существует
        
        
        // Обновляем запись
        $stmt = $db->prepare("
            UPDATE groups 
            SET name = ?, 
                linkname = ? 
            WHERE id = ?
        ");
        
        $stmt->bindValue(1, $name, SQLITE3_TEXT);
        $stmt->bindValue(2, $linkname, SQLITE3_TEXT);
        $stmt->bindValue(3, $group_id, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        return $result !== false && $db->changes() > 0;
    }
?>
