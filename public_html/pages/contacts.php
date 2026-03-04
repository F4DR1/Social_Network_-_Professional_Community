<?php
    require_once '../includes/init.php';
    global $db, $current_user_id;


    // Списки взаимоотношений
    $relationship_lists = [];
    $stmt = $db->prepare('SELECT * FROM users');
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $contacts_list[] = $row;
    }



    // Взаимная подписка
    function getMutualRelationships($db, $current_user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT u.*
            FROM users u
            WHERE u.id IN (
                SELECT r1.related_user_id 
                FROM relationships r1
                WHERE r1.user_id = ? AND r1.related_user_id != ?
                
                INTERSECT
                
                SELECT r2.user_id 
                FROM relationships r2 
                WHERE r2.related_user_id = ? AND r2.user_id != ?
            )
        ");
        $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(3, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(4, $current_user_id, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        return $users;
    }

    // На которых подписан
    function getMyOutgoingRelationships($db, $current_user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT u.*
            FROM users u
            JOIN relationships r ON u.id = r.related_user_id
            WHERE r.user_id = ? AND u.id != ?
            AND u.id NOT IN (
                -- Исключаем тех, кто взаимно подписан
                SELECT r1.related_user_id 
                FROM relationships r1
                WHERE r1.user_id = ? AND r1.related_user_id != ?
                INTERSECT
                SELECT r2.user_id 
                FROM relationships r2 
                WHERE r2.related_user_id = ? AND r2.user_id != ?
            )
        ");
        $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(3, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(4, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(5, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(6, $current_user_id, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        return $users;
    }

    // Которые подписаны
    function getIncomingRelationships($db, $current_user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT u.*
            FROM users u
            JOIN relationships r ON u.id = r.user_id
            WHERE r.related_user_id = ? AND u.id != ?
            AND u.id NOT IN (
                -- Исключаем тех, кто взаимно подписан
                SELECT r1.related_user_id 
                FROM relationships r1
                WHERE r1.user_id = ? AND r1.related_user_id != ?
                INTERSECT
                SELECT r2.user_id 
                FROM relationships r2 
                WHERE r2.related_user_id = ? AND r2.user_id != ?
            )
        ");
        $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(3, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(4, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(5, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(6, $current_user_id, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        return $users;
    }




    $mutual = getMutualRelationships($db, $current_user_id);
    $outgoing = getMyOutgoingRelationships($db, $current_user_id);
    $incoming = getIncomingRelationships($db, $current_user_id);

    

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
    $stylesheet = 'css/contacts.css';
    
    require_once '../enums/layout.php';
    $layout = Layout::Standart;

    require '../layout.php';
?>
