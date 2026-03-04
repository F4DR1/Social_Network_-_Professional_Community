<?php
    require_once '../includes/init.php';
    global $db, $current_user_id;


    // Списки взаимоотношений
    $relationship_lists = [];
    $stmt = $db->prepare('SELECT * FROM users');
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users_list[] = $row;
    }


    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Поиск</h2>

        <h3>Пользователи:</h3>
        <?php foreach ($users_list as $user): ?>
            <?php
                $user_fullname = $user['firstname'] . ' ' . $user['lastname'];
                $user_photo = $user['photo'];
                if ($user['id'] !== $current_user_id):
            ?>
                <div class="profile-panel">
                    <img src="<?= isset($user_photo) ? $user_photo : 'images/empty.webp' ?>" alt="<?= htmlspecialchars($user_fullname) ?>" width=80>
                    <a href="<?= empty($user['linkname']) ? 'user' . $user['id'] : $user['linkname'] ?>" class="fullname-line">
                        <?= htmlspecialchars($user_fullname) ?>
                    </a>
                    <a href="messages?type=user&id=<?= $user['id'] ?>" class="message-line">Написать сообщение</a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Поиск';
    $stylesheet = 'css/search.css';
    
    require_once '../enums/layout.php';
    $layout = Layout::Standart;

    require '../layout.php';
?>
