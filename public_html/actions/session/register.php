<?php
    require_once '../../includes/session_start.php';
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');

    // Проверка на пустые поля
    if (empty($phone) || empty($password) || empty($lastname) || empty($firstname)) {
        echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
        exit;
    }

    // Проверка валидности телефона
    $phone = normalizeRussianPhone($phone);
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Неверный формат телефона']);
        exit;
    }

    // Проверка длины пароля (минимум 6 символов)
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Пароль должен содержать минимум 6 символов']);
        exit;
    }

    // Проверка уникальности телефона
    $stmt = $db->prepare('SELECT id FROM users WHERE phone = ?');
    $stmt->bindValue(1, $phone, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result->fetchArray(SQLITE3_ASSOC) !== false) {
        echo json_encode(['success' => false, 'message' => 'Этот номер телефона уже привязан к другому аккаунту']);
        exit;
    }

    // Хешируем пароль
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Создаем пользователя одной транзакцией
    $db->exec('BEGIN TRANSACTION');

    $stmt = $db->prepare('INSERT INTO users (lastname, firstname, phone, password_hash, created_at) 
                        VALUES (?, ?, ?, ?, datetime("now"))');
    $stmt->bindValue(1, $lastname, SQLITE3_TEXT);
    $stmt->bindValue(2, $firstname, SQLITE3_TEXT);
    $stmt->bindValue(3, $phone, SQLITE3_TEXT);
    $stmt->bindValue(4, $password_hash, SQLITE3_TEXT);

    $result = $stmt->execute();

    if (!$result) {
        $db->exec('ROLLBACK');
        echo json_encode(['success' => false, 'message' => 'Ошибка при регистрации. Попробуйте позже']);
        exit;
    }

    $user_id = $db->lastInsertRowID();
    $db->exec('COMMIT');

    // Автоматическая авторизация после регистрации
    $_SESSION['user_id'] = $user_id;

    echo json_encode([
        'success' => true, 
        'message' => 'Регистрация успешна! Добро пожаловать в профессиональную сеть.'
    ]);
    exit;
?>



<?php
    /**
     * Нормализует российский телефон к формату +7XXXXXXXXXX
     * Примеры:
     * 8 (916) 123-45-67 → +79161234567
     * +7 916 123 45 67 → +79161234567
     * 89161234567 → +79161234567
     */
    function normalizeRussianPhone($phone) {
        // Убираем все НЕ цифры
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        if (empty($phone)) return '';
        
        // Если 11 цифр и начинается с 8 → меняем на +7
        if (strlen($phone) === 11 && $phone[0] === '8') {
            $phone = '7' . substr($phone, 1);
        }
        
        // Если 10 цифр (без кода) → добавляем +7
        if (strlen($phone) === 10 && preg_match('/^9\d{9}$/', $phone)) {
            $phone = '7' . $phone;
        }
        
        // Финальная проверка: ровно 11 цифр, начинается с 7
        if (strlen($phone) === 11 && $phone[0] === '7') {
            return '+7' . substr($phone, 1);
        }
        
        return '';  // Неверный формат
    }
?>
