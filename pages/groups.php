<?php
    require_once '../includes/init.php';
    global $db_frontend, $current_user_id;

    $sql = "
        SELECT 
            g.id,
            g.name,
            g.linkname,
            g.photo,
            gm.role_id,
            gm.joined_at,
            (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id = g.id) as member_count,
            (SELECT gr.name FROM group_roles gr WHERE gr.id = gm.role_id) as role_name
        FROM groups g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ?
        ORDER BY gm.joined_at DESC
    ";
    $groups_list = $db_frontend->fetchAll($sql, [$current_user_id]);
    
    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Группы</h2>

        <?php if (empty($groups_list)): ?>
            <p>Вы не подписаны ни на одну группу.</p>
        <?php else: ?>
            <?php foreach ($groups_list as $group): ?>
                <div class="group-panel">
                    <img src="<?= $group['photo'] ?? 'images/empty.webp' ?>" alt="<?= htmlspecialchars($group['name']) ?>" width=80>
                    <a href="<?= empty($group['linkname']) ? 'group' . $group['id'] : $group['linkname'] ?>" class="name-line">
                        <?= htmlspecialchars($group['name']) ?>
                    </a>
                    <a href="messages?type=group&id=<?= $group['id'] ?>" class="message-line">Написать в чат группы</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="right-container">
    <div class="container">
        <button id="createGroup">Создать группу</button>
    </div>
</div>

<div class="modal" id="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Создание группы</h2>
        </div>
        <div class="modal-main">
            <div class="input-field">
                <input type="text" id="groupName" name="name" required autocomplete="name">
                <label>Название группы*</label>
            </div>
            <p class="message" id="errorMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="cancel-btn" id="cancelButton">Отмена</button>
            <button class="accept-btn" id="acceptButton">Создать группу</button>
        </div>
    </div>
</div>



<script>
    window.appData = <?= json_encode([
        'currentUserId' => $current_user_id
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Группы';
    $scripts = [
        'js/groups.js'
    ];
    $stylesheets = [
        'css/groups.css'
    ];
    require_once '../enums/layout.php';
    $layout = Layout::Standart;
    require '../layout.php';
?>
