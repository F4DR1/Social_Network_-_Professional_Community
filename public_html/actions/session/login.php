<?php
    require_once '../../includes/session_start.php';
    require_once '../../database/db_config.php';
    header('Content-Type: application/json');

    // Получаем данные из POST
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Проверка на пустые поля
    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
        exit;
    }

    
    $phone = normalizeRussianPhone($login);
    if (!empty($phone)) $login = $phone;


    // Ищем пользователя сначала по email, потом по phone
    $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE email = ? OR phone = ?");
    $stmt->bindValue(1, $login, SQLITE3_TEXT);
    $stmt->bindValue(2, $login, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Успешная авторизация
        $_SESSION['user_id'] = $user['id'];

        echo json_encode(['success' => true, 'message' => 'Авторизация успешна']);
    } else {
        // Неверные данные
        echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
    }
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
