<?php
    // user_profile.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/elements.php';
    require_once INCLUDES_PATH . '/page_path.php';
    global $currentUserId;

    $userIsAuthorized = !empty($currentUserId);

    $action = $_GET['act'] ?? '';
    $panel = $_GET['p'] ?? '';
    $user = $_GET['user'];  // Пользователя получаем в index.php
    

    // Отформатированные данные пользователя
    $userId = $user['id'];
    $userFullname = $user['fullname'];
    $userNumber = "user$userId";
    $userLinkname = $user['linkname'] ?? $userNumber;
    $userPhoto = $user['photo'];
    $userBanner = $user['banner'];



    if ($userIsAuthorized && $userId !== $currentUserId) {
        // Получаем текущее состояние подписки
        $response = relationshipsGet($currentUserId, $userId);
        $currentIsFollow = $response['success'] ? $response['data']['isFollow'] : false;
        $relatedIsFollow = $response['success'] ? $response['data']['relatedIsFollow'] : false;

        $currentIsBlock = $response['success'] ? $response['data']['isBlock'] : false;
    }

    
    switch ($action) {
        case 'edit':
            // Не пускаем на страницу редактирования группы если не админ
            if (!$userIsAuthorized || $userId !== $currentUserId) header('Location: ' . PATH);
            break;
        
        default:
            break;
    }
    
    ob_start();
?>



<?php switch($action):
    case 'edit': ?>

        <div class="centered-container" id="editProfileDataPanel">
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

                <!-- Основные данные пользователя -->
                <div class="main-data">
                    <div class="form-fields">

                        <!-- Адрес профиля -->
                        <div class="input-field">
                            <span>Адрес:</span>
                            <div class="field">
                                <div class="input-typed">
                                    <span class="included"><?= htmlspecialchars(decodeUrl(PROTOCOL . '://' . HOST . '/')) ?></span>
                                    <input class="user-linkname" type="text" value="<?= htmlspecialchars($userLinkname) ?>" name="name" required autocomplete="name">
                                </div>
                                <span class="hint">Номер пользователя — <u><?= htmlspecialchars($userNumber) ?></u>.</span>
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
                <a href="<?= PATH ?>" class="btn back-btn" id="userPath">
                    <img src="<?= $userPhoto ?>" alt="<?= htmlspecialchars($userFullname) ?>" width=60>
                    <div class="back-btn-text">
                        <h2><?= htmlspecialchars($userFullname) ?></h2>
                        <span>вернуться в профиль</span>
                    </div>
                </a>
            </section>
            
            <!-- Панель кнопок смены категории -->
            <section class="container category-navigation-panel">
                <h2>Категории настроек</h2>
                
                <div class="category-navigation" id="editUserDataCategoryButtonsPanel">
                    <button class="category-btn category-main">
                        <span>Основная информация</span>
                    </button>
                </div>
            </section>
        </div>
        
        
        <script>
            window.appData = <?= json_encode([
                'userPath' => $userLinkname,
                'userId' => $userId
            ]) ?>;
        </script>
        

    <?php break;
    default: ?>

        <!-- Контейнер с информацией о пользователе -->
        <!-- (находится над центральным и боковым контейнерами) -->
        <div class="main-container">
            <section class="container profile-panel">
                <div class="profile-main-info">
                    <!-- Основная информация о пользователе -->
                    <img src="<?= $userBanner ?>" class="user-profile-banner">
                    <img src="<?= $userPhoto ?>" alt="<?= htmlspecialchars($userFullname) ?>" class="user-profile-photo">
                    <h2 class="user-profile-name"><?= htmlspecialchars($userFullname) ?></h2>
                </div>

                <?php if ($userIsAuthorized): ?>
                    <!-- Панель действий с пользователем (только авторизованные пользователи) -->
                    <div class="profile-actions-panel">
                        
                        <!-- Главная кнопка (большая) -->
                        <div class="main-action">
                            <?php if ($userId === $currentUserId): ?>
                                <div>
                                    <!-- Кнопка "Редактировать профиль" -->
                                    <a class="standart-btn" href="<?= htmlspecialchars(PATH . '?act=edit') ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                        </svg>
                                        <span>Редактировать профиль</span>
                                    </a>
                                </div>

                            <?php else: ?>
                                <?php if ($currentIsFollow && $relatedIsFollow): ?>
                                    <div>
                                        <a class="standart-btn" href="<?= BASE_URL ?>/msg?type=user&id=<?= $userId ?>">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                            </svg>
                                            <span>Сообщение</span>
                                        </a>
                                    </div>

                                <?php elseif ($currentIsFollow): ?>
                                    <!-- Кнопка-выпадающий список -->
                                    <div class="custom-dropdown">
                                        <!-- Кнопка-триггер для открытия выпадающего списка -->
                                        <button class="dropdown-trigger" type="button">
                                            <span>Вы подписаны</span>
                                            <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7 10l5 5 5-5z"/>
                                            </svg>
                                        </button>

                                        <!-- Список действий -->
                                        <ul class="dropdown-menu">
                                            <li>
                                                <!-- Кнопка "Отменить заявку" -->
                                                <button class="dropdown-button" id="unfollowButton">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                                    </svg>
                                                    <span>Отменить заявку</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                <?php elseif ($relatedIsFollow): ?>
                                    <!-- Кнопка-выпадающий список -->
                                    <div class="custom-dropdown">
                                        <!-- Кнопка-триггер для открытия выпадающего списка -->
                                        <button class="dropdown-trigger" type="button">
                                            <span>Подписан на вас</span>
                                            <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7 10l5 5 5-5z"/>
                                            </svg>
                                        </button>

                                        <!-- Список действий -->
                                        <ul class="dropdown-menu">
                                            <li>
                                                <!-- Кнопка "Принять заявку" -->
                                                <button class="dropdown-button" id="acceptButton">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                                    </svg>
                                                    <span>Принять заявку</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                <?php else: ?>
                                    <!-- Кнопка "Отправить заявку" -->
                                    <div>
                                        <button class="standart-btn" id="followButton">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                            </svg>
                                            <span>Отправить заявку</span>
                                        </button>
                                    </div>
                                    
                                <?php endif; ?>

                            <?php endif; ?>
                        </div>


                        <!-- Дополнительные действия (маленькие кнопки) -->
                        <div class="base-actions">
                            <?php if ($userId === $currentUserId): ?>
                                <!-- Кнопка "Настройки" -->
                                <a href="<?= htmlspecialchars(BASE_URL . '/settings') ?>" class="small-btn">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
                                    </svg>
                                </a>
                                
                            <?php else: ?>
                                <?php if (!$currentIsFollow || !$relatedIsFollow): ?>
                                    <!-- Кнопка "Написать сообщение" -->
                                    <a class="small-btn" id="baseMessageUserButton" href="<?= BASE_URL ?>/msg?type=user&id=<?= $userId ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/>
                                        </svg>
                                    </a>

                                <?php endif; ?>

                            <?php endif; ?>
                        </div>


                        <!-- Остальные действия (выпадающее меню со списком кнопок) -->
                        <div class="contact-actions">
                            <!-- Кнопка-выпадающий список -->
                            <div class="custom-dropdown">
                                <!-- Кнопка-триггер для открытия выпадающего списка -->
                                <button class="dropdown-trigger" type="button">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </button>

                                <!-- Список действий -->
                                <ul class="dropdown-menu">
                                    <?php if ($userId === $currentUserId): ?>
                                        <span class="stub-message">Пока нет действий</span>

                                    <?php else: ?>
                                        <?php if ($currentIsFollow && $relatedIsFollow): ?>
                                            <li>
                                                <!-- Кнопка "Удалить из контактов" -->
                                                <button class="dropdown-button" id="deleteButton">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                                    </svg>
                                                    <span>Удалить из контактов</span>
                                                </button>
                                            </li>
                                            <li>
                                                <!-- Добавить в список контактов (выпадающее меню со списком кнопок) -->
                                                <div class="contact-list-actions">
                                                    <!-- Кнопка-выпадающий список -->
                                                    <div class="custom-dropdown">
                                                        <!-- Кнопка-триггер для открытия выпадающего списка -->
                                                        <button class="dropdown-trigger" type="button">
                                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                                            </svg>
                                                            <span>Добавить в список контактов</span>
                                                        </button>

                                                        <!-- Список действий -->
                                                        <ul class="dropdown-menu" id="contactList">
                                                            <!-- Списки контактов получаются в js -->
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>
                                            
                                        <?php endif; ?>
                                        <?php if ($currentIsBlock): ?>
                                            <li>
                                                <!-- Кнопка "Удалить из чёрного списка" -->
                                                <button class="dropdown-button" id="removeBlackListButton">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"/>
                                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" stroke-dasharray="4 3"/>
                                                    </svg>
                                                    <span>Удалить из чёрного списка</span>
                                                </button>
                                            </li>

                                        <?php else: ?>
                                            <li>
                                                <!-- Кнопка "Добавить в чёрный список" -->
                                                <button class="dropdown-button" id="addBlackListButton">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"/>
                                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                                    </svg>
                                                    <span>Добавить в чёрный список</span>
                                                </button>
                                            </li>

                                        <?php endif; ?>

                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                    </div>
                <?php endif; ?>

                <div class="profile-description-info">
                    <!-- Тут информация о пользователе в дальнейшем -->
                    <span class="stub-message">Здесь скоро будет различная информация о пользователе (из настроек профиля)</span>
                </div>
            </section>
        </div>


        <!-- Центральный контейнер -->
        <div class="centered-container">
            <!-- Основной контейнер (строка навигации) -->
            <section class="container profile-navigation">
                <!-- Панель навигации по профилю -->
                <ul>
                    <li class="posts-panel-btn panel-btn">
                        <button class="small-btn" id="postsNavigationButton">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                <polyline points="15,2 15,7 20,7"/>
                                <line x1="8" y1="10" x2="16" y2="10"/>
                                <line x1="8" y1="14" x2="16" y2="14"/>
                                <line x1="8" y1="18" x2="12" y2="18"/>
                            </svg>
                            <span>Посты</span>
                        </button>
                    </li>
                    <li class="skills-panel-btn panel-btn">
                        <button class="small-btn" id="skillsNavigationButton">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <polygon points="13 5 8 13 11 13 10 19 16 11 13 11"/>
                            </svg>
                            <span>Навыки</span>
                        </button>
                    </li>
                </ul>
            </section>

            <?php switch ($panel):
                case 'skills': ?>
                    <!-- Контейнер скиллов -->
                    <section class="container user-skills-panel">
                        <h2>Навыки</h2>
                        <?php if ($userId === $currentUserId): ?>
                            <!-- Кнопка настройки своих навыков -->
                            <button class="button user-skills-edit-btn" id="openEditUserSkillsButton">Настроить</button>
                        <?php endif; ?>

                        <!-- Список навыков пользователя -->
                        <div class="user-skills-list"></div>
                    </section>

                <?php break;
                default: ?>
                    <?php if ($userId === $currentUserId): ?>
                        <!-- Контейнер создания постов -->
                        <?= postCreationField(); ?>
                    <?php endif; ?>
                    <!-- Контейнер постов -->
                    <?= postsPanel(); ?>

                <?php break; ?>
            <?php endswitch; ?>
        </div>



        <script>
            window.appData = <?= json_encode([
                'currentIsBlock' => ($userIsAuthorized && $userId !== $currentUserId) ? $currentIsBlock : false,
                'path' => PATH,
                'currentUserId' => $currentUserId,
                'userId' => $userId,
                'panel' => $panel,
                'postsType' => 'user'
            ]) ?>;
        </script>
        
    <?php break; ?>
<?php endswitch; ?>



<?php
    $content = ob_get_clean();
    $title = $userFullname;
    $scripts = [
        'elements/custom_dropdown.js'
    ];
    $stylesheets = [
        'elements/custom_dropdown.css'
    ];

    switch ($action) {
        case 'edit':
            array_push($scripts,
                'pages/user_profile_edit.js'
            );
            array_push($stylesheets,
                'pages/user_profile_edit.css'
            );
            break;
        
        default:
            array_push($scripts,
                'pages/user_profile.js'
            );
            array_push($stylesheets,
                'pages/user_profile.css',
            );
            switch ($panel) {
                case 'skills':
                    array_push($scripts,
                        'elements/skills.js'
                    );
                    array_push($stylesheets,
                        'elements/skills.css'
                    );
                    break;

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
