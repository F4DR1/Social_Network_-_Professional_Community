<?php
    // user_profile.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/elements.php';
    require_once INCLUDES_PATH . '/page_path.php';
    global $currentUserId;

    $userIsAuthorized = !empty($currentUserId);


    $panel = $_GET['p'] ?? '';
    $user = $_GET['user'];  // Пользователя получаем в index.php
    

    // Отформатированные данные пользователя
    $user_fullname = $user['fullname'];
    $user_photo = $user['photo'];



    if ($userIsAuthorized && $user['id'] !== $currentUserId) {
        // Получаем текущее состояние подписки
        $response = relationshipsGet($currentUserId, $user['id']);
        $currentIsFollow = $response['success'] ? $response['data']['isFollow'] : false;
        $relatedIsFollow = $response['success'] ? $response['data']['relatedIsFollow'] : false;
    }
    
    ob_start();
?>



<!-- Контейнер с информацией о пользователе -->
<!-- (находится над центральным и боковым контейнерами) -->
<div class="main-container">
    <div class="container">
        <img src="<?= $user_photo ?>" alt="<?= htmlspecialchars($user_fullname) ?>" class="user-profile-photo">
        <h2 class="user-profile-name"><?= htmlspecialchars($user_fullname) ?></h2>

        <?php if ($userIsAuthorized): ?>
            <!-- Панель действий с пользователем -->
            <div class="profile-actions-panel">
                <?php if ($user['id'] !== $currentUserId): ?>

                    <!-- Главная кнопка (большая) -->
                    <div class="main-action">
                        <?php if ($currentIsFollow && $relatedIsFollow): ?>
                            <div>
                                <button class="standart-btn" id="mainMessageUserButton">
                                    <span>Сообщение</span>
                                </button>
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
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
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
                    </div>


                    <!-- Дополнительные действия (маленькие кнопки) -->
                    <div class="base-actions">
                        <?php if (!$currentIsFollow || !$relatedIsFollow): ?>
                            <!-- Кнопка "Написать сообщение" -->
                            <button class="small-btn" id="baseMessageUserButton">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>


                    <!-- Остальные действия (выпадающее меню со списком кнопок) -->
                    <div class="contact-actions">
                        <?php if ($currentIsFollow && $relatedIsFollow): ?>
                            <!-- Кнопка-выпадающий список -->
                            <div class="custom-dropdown">
                                <!-- Кнопка-триггер для открытия выпадающего списка -->
                                <button class="dropdown-trigger" type="button">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </button>

                                <!-- Список действий -->
                                <ul class="dropdown-menu">
                                    <li>
                                        <!-- Кнопка "Удалить из контактов" -->
                                        <button class="dropdown-button" id="deleteButton">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                            </svg>
                                            <span>Удалить из контактов</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Главная кнопка (большая) -->
                    <div class="main-action">
                        <div>
                            <button class="standart-btn" id="selfEditProfile">
                                <span>Редактировать профиль</span>
                            </button>
                        </div>
                    </div>

                    <!-- Дополнительные действия (маленькие кнопки) -->
                    <div class="base-actions">
                        <!-- Кнопка "Настройки" -->
                        <button class="small-btn" id="selfSettings">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
                            </svg>
                        </button>
                    </div>

                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div>
            <!-- Тут информация о пользователе в дальнейшем -->
        </div>
    </div>
</div>


<!-- Центральный контейнер -->
<div class="centered-container">
    <!-- Основной контейнер (строка навигации) -->
    <div class="container profile-navigation">
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
    </div>

    <?php switch ($panel):
        case 'skills': ?>
            <!-- Контейнер скиллов -->
            <section class="container skills-panel">
                <h2>Навыки</h2>
                <?php if ($user['id'] === $currentUserId): ?>
                    <!-- Кнопка настройки своих навыков -->
                    <button id="openEditUserSkillsButton" class="button user-skills-btn">Настроить</button>
                <?php endif; ?>

                <!-- Список навыков пользователя -->
                <div id="skillsList" class="skills-list"></div>
            </section>

        <?php break;
        
        default: ?>
            <?php if ($user['id'] === $currentUserId): ?>
                <!-- Контейнер создания постов -->
                <?= postCreationField(); ?>
            <?php endif; ?>
            <!-- Контейнер постов -->
            <?= postsPanel(); ?>

        <?php break; ?>
    <?php endswitch; ?>
</div>


<!-- Боковой контейнер (находится справа) -->
<!-- <div class="right-container">
    
</div> -->



<script>
    window.appData = <?= json_encode([
        'path' => PATH,
        'currentUserId' => $currentUserId,
        'userId' => $user['id'],
        'panel' => $panel,
        'postsType' => 'user'
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = $user_fullname;
    $scripts = [
        'pages/user_profile.js',
        'elements/custom_dropdown.js'
    ];
    $stylesheets = [
        'pages/user_profile.css',
        'elements/custom_dropdown.css'
    ];
    
    switch ($panel) {
        // Загружаем всё что связано с главным меню профиля (но не связано с другими меню)
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

    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
