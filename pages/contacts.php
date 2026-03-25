<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;


    // Заменить запросы к бд на запросы к API
    // // Взаимная подписка
    // function getMutualRelationships(FrontendDB $db_frontend, int $currentUserId): array
    // {
    //     $sql = "
    //         SELECT DISTINCT u.*
    //         FROM users u
    //         JOIN relationships r1 
    //             ON r1.related_user_id = u.id
    //         JOIN relationships r2 
    //             ON r2.user_id = u.id
    //         AND r2.related_user_id = r1.user_id
    //         WHERE r1.user_id = ?
    //         AND u.id <> ?
    //     ";

    //     return $db_frontend->fetchAll($sql, [
    //         $currentUserId,
    //         $currentUserId
    //     ]);
    // }

    // // На которых подписан (исключая взаимных)
    // function getMyOutgoingRelationships(FrontendDB $db_frontend, int $currentUserId): array
    // {
    //     $sql = "
    //         SELECT DISTINCT u.*
    //         FROM users u
    //         JOIN relationships r 
    //             ON u.id = r.related_user_id
    //         WHERE r.user_id = ?
    //         AND u.id <> ?
    //         AND NOT EXISTS (
    //             SELECT 1
    //             FROM relationships r2
    //             WHERE r2.user_id = u.id
    //                 AND r2.related_user_id = ?
    //         )
    //     ";

    //     return $db_frontend->fetchAll($sql, [
    //         $currentUserId,
    //         $currentUserId,
    //         $currentUserId,
    //     ]);
    // }

    // // Которые подписаны (исключая взаимных)
    // function getIncomingRelationships(FrontendDB $db_frontend, int $currentUserId): array
    // {
    //     $sql = "
    //         SELECT DISTINCT u.*
    //         FROM users u
    //         JOIN relationships r 
    //             ON u.id = r.user_id
    //         WHERE r.related_user_id = ?
    //         AND u.id <> ?
    //         AND NOT EXISTS (
    //             SELECT 1
    //             FROM relationships r2
    //             WHERE r2.user_id = ?
    //                 AND r2.related_user_id = u.id
    //         )
    //     ";

    //     return $db_frontend->fetchAll($sql, [
    //         $currentUserId,
    //         $currentUserId,
    //         $currentUserId,
    //     ]);
    // }

    
    $sections = [
        ['type' => 'mutual-relationships', 'title' => 'Ваши контакты:'],
        ['type' => 'outgoing-relationships', 'title' => 'Вы подписаны:'],
        ['type' => 'incoming-relationships', 'title' => 'Подписаны на вас:']
    ];

    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Контакты</h2>

        <?php foreach ($sections as $section): ?>
            <div class="category" id="<?= $section['type'] ?>">
                <h3 class="title" data-count=""><?= $section['title'] ?></h3>
                <div class="list"></div>
            </div>
        <?php endforeach ?>
        
    </div>
</div>



<script>
    window.appData = <?= json_encode([
        'currentUserId' => $currentUserId
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Контакты';
    $scripts = [
        'category_elements.js',
        'contacts.js'
    ];
    $stylesheets = [
        'elements/category.css',
        'elements/user_card.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
