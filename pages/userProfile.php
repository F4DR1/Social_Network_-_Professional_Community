<?php
    require_once 'includes/init.php';
    global $db_frontend, $current_user_id;


    // Отформатированные данные пользователя
    $user_fullname = $user['firstname'] . ' ' . $user['lastname'];
    $user_photo = $user['photo'];
    
    // Получаем статус пользователя
    $user_status = $db_frontend->fetchOne('SELECT * FROM user_statuses WHERE id = ?', [$user['status_id']]);

    // Получаем роль пользователя
    $user_role = $db_frontend->fetchOne('SELECT * FROM user_roles WHERE id = ?', [$user['status_id']]);



    if ($user['id'] !== $current_user_id) {
        // Списки взаимоотношений
        $relationship_lists = $db_frontend->fetchAll('SELECT * FROM relationship_lists');
    }

    
    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <img src="<?= $user_photo ?? 'images/empty.webp' ?>" alt="<?= htmlspecialchars($user_fullname) ?>" width=200>
        <h1><?= htmlspecialchars($user_fullname) ?></h1>

        <div class="profile-actions-panel">
            <?php if ($user['id'] !== $current_user_id): ?>


                <!-- Главная кнопка -->
                <div class="main-action">
                    <div id="main-message">
                        <a href="messages?type=user&id=<?= $user['id'] ?>" class="standart-btn">Сообщение</a>
                    </div>
                    <div id="main-request">
                        <button class="standart-btn" id="followButton">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                            </svg>
                            <span>Отправить заявку</span>
                        </button>
                    </div>
                    <div id="main-request-following">
                        <div class="action-dropdown">
                            <button class="standart-btn action-trigger">
                                <span>Вы подписаны</span>
                                <svg viewBox="0 0 16 12" width="16" height="12" fill="currentColor" style="width: 16px; height: 12px;">
                                    <path d="m8 6.778 3.773-3.107a.75.75 0 1 1 .954 1.158l-4.25 3.5a.75.75 0 0 1-.954 0l-4.25-3.5a.75.75 0 0 1 .954-1.158z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-list">
                                <li>
                                    <button class="dropdown-button" id="unfollowButton">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                        </svg>
                                        <span>Отменить заявку</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div id="main-request-follower">
                        <div class="action-dropdown">
                            <button class="standart-btn action-trigger">
                                <span>Подписан на вас</span>
                                <svg viewBox="0 0 16 12" width="16" height="12" fill="currentColor" style="width: 16px; height: 12px;">
                                    <path d="m8 6.778 3.773-3.107a.75.75 0 1 1 .954 1.158l-4.25 3.5a.75.75 0 0 1-.954 0l-4.25-3.5a.75.75 0 0 1 .954-1.158z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-list">
                                <li>
                                    <button class="dropdown-button" id="acceptButton">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                        </svg>
                                        <span>Принять заявку</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>


                <div class="base-actions">
                    <div id="base-action-message">
                        <a href="messages?type=user&id=<?= $user['id'] ?>" class="small-btn">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/>
                            </svg>
                        </a>
                    </div>
                </div>


                <div class="contact-actions">
                    <div id="contact-action">
                        <div class="action-dropdown">
                            <button class="action-trigger has-lists">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </button>

                            <ul class="dropdown-list">
                                <li>
                                    <button class="dropdown-button" id="deleteButton">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                        </svg>
                                        <span>Удалить из контактов</span>
                                    </button>
                                </li>
                                <li class="lists-wrapper">
                                    <button class="dropdown-button lists-toggle">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                        <span>Изменить список</span>
                                        <svg class="dropdown-arrow" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                            <path d="M7 10l5 5 5-5z"/>
                                        </svg>
                                    </button>
                                    
                                    <ul class="lists-dropdown">
                                        <?php
                                            $current_list_id = $current_user_relationship['relationship_list_id'] ?? 1;
                                            foreach ($relationship_lists as $list):
                                        ?>
                                            <li>
                                                <button class="dropdown-button">
                                                    <?php if ($current_list_id == $list['id']): ?>
                                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="opacity: .25">
                                                            <circle cx="12" cy="12" r="8"/>
                                                        </svg>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($list['name']) ?></span>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>
                
            <?php else: ?>
                <!-- Это свой профиль -->
                <a href="edit-profile" class="standart-btn">Редактировать профиль</a>
                <a href="settings" class="small-btn">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
                    </svg>
                </a>

            <?php endif; ?>
        </div>

        <div>
            <p>Роль: <?= $user_role['name']; ?></p>
            <p>Статус: <?= $user_status['name']; ?></p>
        </div>
    </div>
</div>


<script>
    window.appData = <?= json_encode([
        'currentUserId' => $current_user_id,
        'userId' => $user['id']
    ]) ?>;
</script>
<!-- <script src="js/profile.js"></script>
<script src="js/profile_dropdown.js"></script> -->



<?php
    $content = ob_get_clean();
    $title = $user_fullname;
    $scripts = [
        'js/profile.js',
        'js/profile_dropdown.js'
    ];
    $stylesheets = [
        'css/profile.css'
    ];
    require_once 'enums/layout.php';
    $layout = Layout::Standart;
    require 'layout.php';
?>
