<?php
    // group_profile.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/elements.php';
    require_once INCLUDES_PATH . '/page_path.php';
    global $currentUserId;

    $userIsAuthorized = !empty($currentUserId);

    $action = $_GET['act'] ?? '';
    $panel = $_GET['p'] ?? '';
    $group = $_GET['group'];  // Группу получаем в index.php


    // Отформатированные данные группы
    $groupId = $group['id'];
    $groupName = $group['name'];
    $groupNumber = "group$groupId";
    $groupLinkname = $group['linkname'] ?? $groupNumber;
    $groupPhoto = $group['photo'];

    
    $isAdmin = false;
    if ($userIsAuthorized) {
        // Проверяем подписку
        $response = groupsStatusSubscribe($groupId);
        $isSubscribe = $response['success'] ? $response['data']['isSubscribe'] : false;
        $isOwner = $response['success'] ? $response['data']['isOwner'] : false;

        // Проверяем админ ли
        if ($isSubscribe) {
            $response = groupsUserIsAdmin($groupId, $currentUserId);
            $isAdmin = $response['success'] ? $response['data']['isAdmin'] : false;
        }
    }


    switch ($action) {
        case 'edit':
            // Не пускаем на страницу редактирования группы если не админ
            if (!$isAdmin) header('Location: ' . PATH);
            break;
        
        default:
        
            break;
    }

    ob_start();
?>



<?php switch($action):
    case 'edit': ?>

        <div class="centered-container" id="editGroupDataPanel">
            <section class="container data">
                <h2 class="container-title"></h2>

                <!-- Панель сообщения -->
                <div class="message-panel">
                    <svg class="message-icon" viewBox="0 0 24 24" width="50" height="50" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                    </svg>
                    <div class="message-main">
                        <h3 class="message-title"></h3>
                        <p class="message-text"></p>
                    </div>
                </div>

                <!-- Основные данные группы -->
                <div class="main-data">
                    <div class="form-fields">

                        <!-- Имя группы -->
                        <div class="input-field">
                            <span>Название:</span>
                            <div class="field">
                                <input class="group-name" type="text" id="groupName" value="<?= htmlspecialchars($groupName) ?>" name="name" required autocomplete="name">
                            </div>
                        </div>
                        <!-- Адрес профиля -->
                        <div class="input-field">
                            <span>Адрес:</span>
                            <div class="field">
                                <div class="input-typed">
                                    <span class="included"><?= htmlspecialchars(PROTOCOL . '://' . HOST . '/') ?></span>
                                    <input class="group-linkname" type="text" value="<?= htmlspecialchars($groupLinkname) ?>" name="name" required autocomplete="name">
                                </div>
                                <span class="hint">Номер группы — <u><?= htmlspecialchars($groupNumber) ?></u>.</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Кнопка "Сохранить" -->
                <button class="standart-btn" id="saveData">Сохранить</button>

            </section>
        </div>

        <div class="right-container">
            <!-- Кнопка возвращения к профилю -->
            <section class="container back-panel">
                <a href="<?= PATH ?>" class="btn back-btn" id="groupPath">
                    <img src="<?= $groupPhoto ?>" alt="<?= htmlspecialchars($groupName) ?>" width=60>
                    <div class="back-btn-text">
                        <h2><?= htmlspecialchars($groupName) ?></h2>
                        <span>вернуться в профиль</span>
                    </div>
                </a>
            </section>
            
            <!-- Панель кнопок смены категории -->
            <section class="container category-navigation-panel">
                <h2>Категории настроек</h2>
                
                <div class="category-navigation" id="editGroupDataCategoryButtonsPanel">
                    <button class="category-btn category-main">
                        <span>Основная информация</span>
                    </button>
                </div>
            </section>
        </div>
        
        
        <script>
            window.appData = <?= json_encode([
                'groupPath' => $groupLinkname,
                'groupId' => $groupId
            ]) ?>;
        </script>
        

    <?php break;
    default: ?>

        <div class="main-container">
            <section class="container profile-panel">
                <img src="<?= $groupPhoto ?>" alt="<?= htmlspecialchars($groupName) ?>" width=200>
                <h2><?= htmlspecialchars($groupName) ?></h2>
                
                <?php if ($userIsAuthorized): ?>
                    <!-- Панель действий с группой -->
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
                <?php endif; ?>

            </section>
        </div>

        <div class="centered-container">
            <?php if ($userIsAuthorized && $isSubscribe && $isAdmin): ?>
                <?= postCreationField(); ?>
            <?php endif; ?>

            <?= postsPanel(); ?>
        </div>

        <div class="right-container">
            <?php if ($userIsAuthorized && $isSubscribe && $isAdmin): ?>
                <!-- Панель управления группой -->
                <section class="container group-edit">
                    <a href="<?= htmlspecialchars(PATH . '?act=edit') ?>" class="btn">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
                        </svg>
                        <span>Управление группой</span>
                    </a>
                </section>
            <?php endif; ?>

            <!-- Панель списка участников -->
            <section class="container members-panel">
                <h2>Участники <span class="count" id="membersCount"></span></h2>
                <div class="members-list" id="membersList"></div>
            </section>
        </div>
        

        
        <script>
            window.appData = <?= json_encode([
                'postsType' => 'group',
                'groupId' => $groupId
            ]) ?>;
        </script>
        
    <?php break; ?>
<?php endswitch; ?>



<?php
    $content = ob_get_clean();
    $title = $groupName;
    $scripts = [
        'elements/custom_dropdown.js'
    ];
    $stylesheets = [
        'elements/custom_dropdown.css'
    ];
    
    switch ($action) {
        case 'edit':
            array_push($scripts,
                'pages/group_profile_edit.js'
            );
            array_push($stylesheets,
                'pages/group_profile_edit.css'
            );
            break;
        
        default:
            array_push($scripts,
                'pages/group_profile.js'
            );
            array_push($stylesheets,
                'pages/group_profile.css'
            );
            switch ($panel) {
                // Загружаем всё что связано с главным меню профиля (но не связано с другими меню профиля)
                default:
                    array_push($scripts,
                        'elements/files_upload.js',
                        'elements/posts.js'
                    );
                    array_push($stylesheets,
                        'elements/files_upload.css',
                        'elements/posts.css',
                        'elements/post_create.css'
                    );
                    break;
            }
            break;
    }
    
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
