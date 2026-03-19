<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $current_user_id;

    // Списки взаимоотношений
    $contacts_list = $db_frontend->fetchAll('SELECT * FROM users');


    // Взаимная подписка
    function getMutualRelationships(FrontendDB $db_frontend, int $current_user_id): array
    {
        $sql = "
            SELECT DISTINCT u.*
            FROM users u
            JOIN relationships r1 
                ON r1.related_user_id = u.id
            JOIN relationships r2 
                ON r2.user_id = u.id
            AND r2.related_user_id = r1.user_id
            WHERE r1.user_id = ?
            AND u.id <> ?
        ";

        return $db_frontend->fetchAll($sql, [
            $current_user_id,
            $current_user_id
        ]);
    }

    // На которых подписан (исключая взаимных)
    function getMyOutgoingRelationships(FrontendDB $db_frontend, int $current_user_id): array
    {
        $sql = "
            SELECT DISTINCT u.*
            FROM users u
            JOIN relationships r 
                ON u.id = r.related_user_id
            WHERE r.user_id = ?
            AND u.id <> ?
            AND NOT EXISTS (
                SELECT 1
                FROM relationships r2
                WHERE r2.user_id = u.id
                    AND r2.related_user_id = ?
            )
        ";

        return $db_frontend->fetchAll($sql, [
            $current_user_id,
            $current_user_id,
            $current_user_id,
        ]);
    }

    // Которые подписаны (исключая взаимных)
    function getIncomingRelationships(FrontendDB $db_frontend, int $current_user_id): array
    {
        $sql = "
            SELECT DISTINCT u.*
            FROM users u
            JOIN relationships r 
                ON u.id = r.user_id
            WHERE r.related_user_id = ?
            AND u.id <> ?
            AND NOT EXISTS (
                SELECT 1
                FROM relationships r2
                WHERE r2.user_id = ?
                    AND r2.related_user_id = u.id
            )
        ";

        return $db_frontend->fetchAll($sql, [
            $current_user_id,
            $current_user_id,
            $current_user_id,
        ]);
    }



    $mutual = getMutualRelationships($db_frontend, $current_user_id);
    $outgoing = getMyOutgoingRelationships($db_frontend, $current_user_id);
    $incoming = getIncomingRelationships($db_frontend, $current_user_id);

    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Контакты</h2>

        <div>
            <h3 data-count="(<?= count($mutual) ?>)">Ваши контакты:</h3>
            <?php if (empty($mutual)): ?>
                <p>У вас нет контактов.</p>
            <?php else: ?>
                <?php foreach ($mutual as $user): ?>
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
            <?php endif; ?>
        </div>

        <div>
            <h3 data-count="(<?= count($outgoing) ?>)">Вы подписаны:</h3>
            <?php if (empty($outgoing)): ?>
                <p>Вы ни на кого не подписаны.</p>
            <?php else: ?>
                <?php foreach ($outgoing as $user): ?>
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
            <?php endif; ?>
        </div>

        <div>
            <h3 data-count="(<?= count($incoming) ?>)">Подписаны на вас:</h3>
            <?php if (empty($incoming)): ?>
                <p>На вас никто не подписан.</p>
            <?php else: ?>
                <?php foreach ($incoming as $user): ?>
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
            <?php endif; ?>
        </div>
    </div>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Контакты';
    $scripts = [];
    $stylesheets = [
        'contacts.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
