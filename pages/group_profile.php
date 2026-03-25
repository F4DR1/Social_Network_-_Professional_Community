<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/elements.php';
    global $currentUserId;

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    $action = $_GET['act'] ?? '';


    // Отформатированные данные группы
    $groupId = $group['id'];
    $groupName = $group['name'];
    $groupNumber = 'group' . $groupId;
    $groupLinkname = $group['linkname'] ?? $groupNumber;
    $groupPhoto = $group['photo'];

    
    // Проверяем подписку
    $response = groupsStatusSubscribe($groupId);
    $isSubscribe = $response['success'] ? $response['data']['isSubscribe'] : false;
    $isOwner = $response['success'] ? $response['data']['isOwner'] : false;


    // Проверяем админ ли
    if ($isSubscribe) {
        $response = groupsUserIsAdmin($groupId, $currentUserId);
        $isAdmin = $response['success'] ? $response['data']['isAdmin'] : false;
    }


    if (empty($action)) {
        
    } elseif ($action === 'edit') {
        // Не пускаем на страницу редактирования группы если не админ
        if (!$isAdmin) header('Location: ' . $path);
    }

    ob_start();
?>



<?php if (empty($action)): ?>
    <div class="main-container">
        <div class="container">
            <img src="<?= $groupPhoto ?? 'images/empty.webp' ?>" alt="<?= htmlspecialchars($groupName) ?>" width=200>
            <h1><?= htmlspecialchars($groupName) ?></h1>
            <p><?= $action ?></p>
            
            <div class="profile-actions-panel">

                <!-- Главная кнопка -->
                <div class="main-action">
                    <?php if (!$isSubscribe): ?>
                        <div id="mainRequestSubscribe" class="active">
                            <button class="standart-btn" id="subscribeButton">
                                <span>Вступить в группу</span>
                            </button>
                        </div>

                    <?php else: ?>
                        <div id="mainRequestUnsubscribe" class="active">
                            <?php if ($isOwner): ?>
                                <div class="action-dropdown">
                                    <button class="standart-btn action-trigger">
                                        <span>Вы владелец</span>
                                    </button>
                                </div>

                            <?php else: ?>
                                <div class="action-dropdown">
                                    <button class="standart-btn action-trigger">
                                        <span>Вы участник</span>
                                        <svg viewBox="0 0 16 12" width="16" height="12" fill="currentColor" style="width: 16px; height: 12px;">
                                            <path d="m8 6.778 3.773-3.107a.75.75 0 1 1 .954 1.158l-4.25 3.5a.75.75 0 0 1-.954 0l-4.25-3.5a.75.75 0 0 1 .954-1.158z"/>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-list">
                                        <li>
                                            <button class="dropdown-button" id="unsubscribeButton">
                                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                                </svg>
                                                <span>Выйти из группы</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                            <?php endif; ?>
                        </div>

                    <?php endif; ?>
                </div>

                <!-- Второстепенная -->
                <div class="second-action">
                    <?php if ($isSubscribe): ?>
                        <div id="secondMessage" class="active">
                            <a href="messages?type=group&id=<?= $groupId ?>" class="btn">Написать в чат</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <div class="centered-container">
        <?php if ($isSubscribe && $isAdmin): ?>
            <?=  postCreationField(); ?>
        <?php endif; ?>

        <?= postsPanel(); ?>
    </div>

    <div class="right-container">
        <?php if ($isSubscribe && $isAdmin): ?>
            <div class="container">
                <a href="<?= htmlspecialchars($path . '?act=edit') ?>" class="btn">Управление</a>
            </div>
        <?php endif; ?>

        <div class="container">
            <h2>Участники <span class="count" id="membersCount"></span></h2>
            <div class="members-list" id="membersList"></div>
        </div>
    </div>
    
    
    <script>
        window.appData = <?= json_encode([
            'postsType' => 'group',
            'groupId' => $groupId
        ]) ?>;
    </script>
    

<?php elseif ($action === 'edit'): ?>
    <div class="centered-container">
        <div class="container">
            <h2>Основная информация</h2>

            <div id="base-info" class="info-panel">
                <svg class="info-icon" viewBox="0 0 64 64">
                    <!-- SVG: Зелёная галочка в круге | Красный крест в круге -->
                    <path class="info-icon-svg" />
                </svg>
                <h3 class="info-title"></h3>
                <p class="info-message"></p>
            </div>

            <div class="form-fields">
                <div class="input-field">
                    <span>Название:</span>
                    <input type="text" id="group-name" value="<?= htmlspecialchars($groupName) ?>" name="name" required autocomplete="name">
                </div>
                <div class="input-field typed">
                    <span>Адрес:</span>
                    <span class="included"><?= htmlspecialchars($protocol . '://' . $host . '/') ?></span>
                    <input type="text" id="group-linkname" value="<?= htmlspecialchars($groupLinkname) ?>" name="name" required autocomplete="name">
                    <span class="hint">Номер группы — <u><?= htmlspecialchars($groupNumber) ?></u>.</span>
                </div>
            </div>

            <button class="standart-btn" id="save-base-info">Сохранить</button>
        </div>
    </div>

    <div class="right-container">
        <div class="container">
            <a class="btn" id="groupPath">
                <img src="<?= $groupPhoto ?? 'images/empty.webp' ?>" alt="<?= htmlspecialchars($groupName) ?>" width=60>
                <h1><?= htmlspecialchars($groupName) ?></h1>
                <p>вернуться к странице</p>
            </a>
        </div>
    </div>
    
    
    <script>
        window.appData = <?= json_encode([
            'groupPath' => $groupLinkname,
            'groupId' => $groupId
        ]) ?>;
    </script>

<?php endif; ?>



<?php
    $content = ob_get_clean();
    $title = $groupName;
    $scripts = [
        'profile_dropdown.js'
    ];
    $stylesheets = [];
    
    if (empty($action)) {
        array_push($scripts,
            'group_profile.js',
            'posts.js'
        );
        array_push($stylesheets,
            'pages/group_profile.css',
            'elements/post_create.css',
            'elements/post.css'
        );
    } elseif ($action === 'edit') {
        array_push($scripts,
            'group_profile_edit.js'
        );
        array_push($stylesheets,
            'pages/group_profile_edit.css',
            'elements/input_field.css'
        );
    }
    
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
