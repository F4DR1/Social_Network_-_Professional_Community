<?php
    // layout.php
    require_once __DIR__ . '/bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/page_path.php';
    global $clientConfig, $currentUser;

    require_once ENUMS_PATH . '/auth.php';
    require_once ENUMS_PATH . '/layout.php';


    $userToken = !empty($currentUser) ? $_COOKIE['auth_token'] : null;
    
    $profileLink = '';


    // Обрабатываем каждый отдельно
    if (empty($layout)) $layout = Layout::Standart;  // Шаблон по умолчанию
    switch ($layout) {
        case Layout::Standart:
            // Получаем данные пользователя
            if (!empty($currentUser)) {
                $currentUserFullName = $currentUser['fullname'];
                $currentUserPhoto = $currentUser['photo'] ?? null;

                $profileLink = $currentUser['linkname'] ?? 'user' . $currentUser['id'];
            }
            break;
        
        default:
            break;
    }

    // Полная ссылка для return_url
    $returnUrl = urlencode(PROTOCOL . '://' . HOST . PATH);
?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
            $base_title = 'МирПрофи';
            echo $title ?? $base_title;
        ?>
    </title>
    <link rel="icon" href="<?= API ?>/images/static/logotype.png" type="image/x-icon">


    <!-- Конфиг -->
    <script>
        window.APP_CONFIG = <?= isset($clientConfig) ? json_encode($clientConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : json_encode([]) ?>;
    </script>

    
    <!-- Стартовые данные для layout -->
    <?php if ($layout === Layout::Standart): ?>
        <link rel="stylesheet" href="<?= CSS_URL ?>/containers.css?v=<?= time() ?>">
    <?php elseif ($layout === Layout::Mini): ?>
    <?php elseif ($layout === Layout::Micro): ?>
    <?php endif; ?>

    
    <!-- Данные для layout.js -->
    <script>
        window.layoutData = <?= json_encode([
            'userIsAuthorized' => !empty($currentUser),
            'profileLink' => $profileLink
        ]) ?>;
    </script>
    

    <!-- Разметка layout -->
    <link rel="stylesheet" href="<?= CSS_URL ?>/layout.css?v=<?= time() ?>">

    <!-- Главные скрипты -->
    <script src="<?= JS_URL ?>/modal.js?v=<?= time() ?>" type="module"></script>
    <script src="<?= JS_URL ?>/layout.js?v=<?= time() ?>" type="module"></script>

    <!-- Скрипты и стили страницы -->
    <?php
        // Подгружаем скрипты
        if (isset($scripts) && $scripts > 0) {
            foreach ($scripts as $script) {
                $time = time();
                echo "<script src=\"" . JS_URL ."/$script?v=$time\" type=\"module\"></script>";
            }
        }

        // Подгружаем стили
        if (isset($stylesheets) && $stylesheets > 0) {
            foreach ($stylesheets as $sh) {
                echo "<link rel=\"stylesheet\" href=" . CSS_URL . "/$sh>";
            }
        }
    ?>
    <?php if ($layout === Layout::Standart): ?>
        <!-- Загружаем вебсокет -->
        <script src="<?= JS_URL ?>/socket_manager.js?v=<?= time() ?>"></script>
        <script>
            // Создаём глобальный экземпляр сокета
            window.socket = new SocketManager();

            // При переходе на другую страницу соединение корректно закрывается
            window.addEventListener('beforeunload', () => {
                if (window.socket) {
                    window.socket.close();
                }
            });
            
            // Подключаемся к WebSocket
            window.socket.connect('<?= htmlspecialchars($userToken) ?>');
            document.dispatchEvent(new Event('socketReady'));  // Оповещаем всех, что сокет готов
        </script>
    <?php endif; ?>
</head>
<body class="layout-<?= strtolower($layout->name) ?>">
    <?php if ($layout !== Layout::Micro): ?>
        <!-- Шапка (видно в любом layout, кроме Micro) -->
        <header>
            <div class="header-container">
                <!-- Логотип и название соцсети -->
                <a href="/" class="logo-row">
                    <img src="<?= API ?>/images/static/logotype.png" alt="Логотип соцсети" class="logo">
                    <h1><?= htmlspecialchars($base_title) ?></h1>
                </a>
                <?php if ($layout === Layout::Standart): ?>
                    <!-- Элементы пользователя (видно в стандартном layout) -->
                    <div class="header-row">
                        <?php if (empty($currentUser)): ?>
                            <!-- Кнопки входа -->
                            <nav class="auth">
                                <ul>
                                    <li><a href="<?= Auth::Login->text(); ?>?return_url=<?= $returnUrl ?>" class="inline">Войти</a></li>
                                    <li><a href="<?= Auth::Register->text(); ?>?return_url=<?= $returnUrl ?>" class="inline">Регистрация</a></li>
                                </ul>
                            </nav>

                        <?php else: ?>
                            <!-- Выпадающее меню уведомлений -->
                            <div class="notifications-dropdown header-dropdown" id="notificationsDropdown">
                                <!-- Кнопка выпадающего меню со счётчиком уведомлений -->
                                <button class="header-dropdown-trigger" id="notificationsDropdownTrigger" aria-label="Уведомления">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                    </svg>
                                    <span class="counter notifications-counter" id="notificationsCounter"></span>
                                    <svg class="dropdown-arrow" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                        <path d="M7 10l5 5 5-5z"/>
                                    </svg>
                                </button>

                                <!-- Выпадающее меню -->
                                <div class="header-dropdown-content" id="notifications">
                                </div>
                            </div>
                            
                            <!-- Выпадающее меню пользователя -->
                            <nav class="profile-dropdown header-dropdown" id="profileDropdown">
                                <!-- Кнопка выпадающего меню с фото и именем текущего пользователя -->
                                <button class="header-dropdown-trigger" id="profileDropdownTrigger" aria-label="Меню профиля">
                                    <div class="profile-dropdown-avatar">
                                        <img src="<?= $currentUserPhoto ?>" alt="<?= htmlspecialchars($currentUserFullName) ?>" width="32" height="32">
                                    </div>
                                    <span class="profile-dropdown-name"><?= htmlspecialchars($currentUserFullName) ?></span>
                                    <svg class="dropdown-arrow" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                        <path d="M7 10l5 5 5-5z"/>
                                    </svg>
                                </button>
                                
                                <!-- Выпадающее меню с данными пользователя и навигацией -->
                                <div class="header-dropdown-content">
                                    <div class="profile-dropdown-info">
                                        <img src="<?= $currentUserPhoto ?>" alt="<?= htmlspecialchars($currentUserFullName) ?>" width="48" height="48">
                                        <div>
                                            <div class="profile-dropdown-fullname"><?= htmlspecialchars($currentUserFullName) ?></div>
                                            <div class="profile-dropdown-link">@<?= htmlspecialchars($profileLink) ?></div>
                                        </div>
                                    </div>
                                    <ul class="header-dropdown-list">
                                        <li><button id="profileDropdownProfile" class="header-dropdown-button">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                            </svg>
                                            <span>Профиль</span>
                                        </button></li>
                                        <li><button id="profileDropdownSettings" class="header-dropdown-button">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                            </svg>
                                            <span>Настройки</span>
                                        </button></li>
                                        <li><button id="profileDropdownLogout" href="" class="header-dropdown-button logout">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                                            </svg>
                                            <span>Выйти</span>
                                        </button></li>
                                    </ul>
                                </div>
                            </nav>

                        <?php endif; ?>
                    </div>

                <?php endif; ?>
            </div>
        </header>

    <?php endif; ?>

    <main>
        <?php if ($layout === Layout::Standart): ?>
            <!-- Меню навигации (видно в стандартном layout) -->
            <div class="navigation-menu">
                <nav class="navigation">
                    <ul>
                        <?php if (empty($currentUser)): ?>
                            <!-- Кнопки входа -->
                            <li><a href="<?= Auth::Login->text() ?>">Войти</a></li>
                            <li><a href="<?= Auth::Register->text() ?>">Регистрация</a></li>
                        <?php else: ?>
                            <!-- Кнопки навигации -->
                            <li>
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M5.5 21a8.5 8.5 0 0 1 13 0"/>
                                </svg>
                                <a href="<?= $profileLink ?>">Профиль</a>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <line x1="7" y1="8" x2="17" y2="8"/>
                                    <line x1="7" y1="12" x2="17" y2="12"/>
                                    <line x1="7" y1="16" x2="13" y2="16"/>
                                </svg>
                                <a href="feed">Лента</a>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <a href="msg">
                                    Мессенджер
                                    <span class="counter messages-counter" id="messagesCounter"></span>
                                </a>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <circle cx="10" cy="10" r="2.5"/>
                                    <path d="M6 16c0-1.5 2-3 4-3s4 1.5 4 3"/>
                                    <line x1="16" y1="9" x2="19" y2="9"/>
                                    <line x1="16" y1="13" x2="19" y2="13"/>
                                    <line x1="16" y1="17" x2="18" y2="17"/>
                                </svg>
                                <a href="contacts">Контакты</a>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="7" cy="6" r="2.2"/>
                                    <path d="M3.5 13c0-1.5 1.5-2.5 3.5-2.5s3.5 1 3.5 2.5"/>
                                    <circle cx="17" cy="6" r="2.2"/>
                                    <path d="M13.5 13c0-1.5 1.5-2.5 3.5-2.5s3.5 1 3.5 2.5"/>
                                    <circle cx="12" cy="9" r="2.5"/>
                                    <path d="M7 16c0-2 2.5-3.5 5-3.5s5 1.5 5 3.5"/>
                                </svg>
                                <a href="groups">Группы</a>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <a href="search">Поиск</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
        
        <!-- Содержимое страницы -->
        <div class="page-content">
            <?php
                if (isset($content)) {
                    echo $content;
                }
            ?>
        </div>
    </main>

    <?php if ($layout !== Layout::Micro): ?>
        <!-- Подвал (видно в любом layout, кроме Micro) -->
        <footer class="global">
            &copy; <?= htmlspecialchars($base_title) ?>
        </footer>
    <?php endif; ?>

</body>
</html>