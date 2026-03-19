<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $current_user_id;

    // Списки пользователей
    $users_list = $db_frontend->fetchAll('SELECT * FROM users');

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
    $scripts = [];
    $stylesheets = [
        'search.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
