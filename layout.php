<?php
    require_once __DIR__ . '/bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $clientConfig, $currentUserId, $currentUser;

    require_once ENUMS_PATH . '/auth.php';
    require_once ENUMS_PATH . '/layout.php';


    // Шаблон по умолчанию
    if (empty($layout)) {
        $layout = Layout::Standart;
    }

    switch ($layout) {
        case Layout::Standart:
            if (!empty($currentUserId) && !empty($currentUser)) {
                $currentUser_fullname = $currentUser['firstname'] . ' ' . $currentUser['lastname'];
                $currentUser_link = $currentUser['linkname'] ?? 'user' . $currentUserId ;
                $currentUser_photo = $currentUser['photo'] ?? null;
            }
            break;
        
        default:
            break;
    }

    // Полная ссылка для return_url
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    $returnUrl = urlencode($protocol . '://' . $host . $path);
?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
            $base_title = 'NNN';
            echo $title ?? $base_title;
        ?>
    </title>

    <link rel="icon" href="<?= IMAGES_URL ?>/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?= CSS_URL ?>/global.css">

    <script>
        window.APP_CONFIG = <?= isset($clientConfig) ? json_encode($clientConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : json_encode([]) ?>;
    </script>
    <script src="<?= JS_URL . '/modal.js' ?>" type="module"></script>
    <?php
        // Подгружаем скрипты определённых страниц
        if (isset($scripts) && $scripts > 0) {
            foreach ($scripts as $script) {
                echo "<script src=\"" . JS_URL ."/$script\" type=\"module\"></script>";
            }
        }

        // Подгружаем стили определённой страницы
        if (isset($stylesheets) && $stylesheets > 0) {
            foreach ($stylesheets as $sh) {
                echo "<link rel=\"stylesheet\" href=" . CSS_URL . "/$sh>";
            }
        }
    ?>
</head>
<body class="layout-<?= strtolower($layout->name) ?>">
    <?php if ($layout !== Layout::Micro): ?>
        <header>
            <div class="header-container">
                <a href="/" class="logo-row">
                    <img src="<?= IMAGES_URL ?>/logo.png" alt="Логотип NNN" class="logo">
                    <h1>NNN</h1>
                </a>
                <?php if ($layout === Layout::Standart): ?>
                    <div class="header-row">
                        <?php if (empty($currentUserId)): ?>
                            <nav class="auth">
                                <ul>
                                    <li><a href="<?= Auth::Login->text(); ?>?return_url=<?= $returnUrl ?>" class="inline">Войти</a></li>
                                    <li><a href="<?= Auth::Register->text(); ?>?return_url=<?= $returnUrl ?>" class="inline">Регистрация</a></li>
                                </ul>
                            </nav>

                        <?php else: ?>
                            <nav class="profile-dropdown">
                                <button class="profile-trigger" aria-label="Меню профиля">
                                    <div class="profile-avatar">
                                        <img src="<?= $currentUser_photo ?: IMAGES_URL . '/empty.webp' ?>" alt="<?= htmlspecialchars($currentUser_fullname) ?>" width="32" height="32">
                                    </div>
                                    <span class="profile-name"><?= htmlspecialchars($currentUser_fullname) ?></span>
                                    <svg class="dropdown-arrow" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                        <path d="M7 10l5 5 5-5z"/>
                                    </svg>
                                </button>
                                
                                <div class="dropdown-menu">
                                    <div class="profile-info">
                                        <img src="<?= $currentUser_photo ?: IMAGES_URL . '/empty.webp' ?>" alt="<?= htmlspecialchars($currentUser_fullname) ?>" width="48" height="48">
                                        <div>
                                            <div class="profile-fullname"><?= htmlspecialchars($currentUser_fullname) ?></div>
                                            <div class="profile-link">@<?= htmlspecialchars($currentUser['linkname'] ?: 'user' . $currentUserId) ?></div>
                                        </div>
                                    </div>
                                    <ul class="dropdown-list">
                                        <li><a href="<?= $currentUser_link ?>" class="dropdown-link">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                            </svg>
                                            <span>Профиль</span>
                                        </a></li>
                                        <li><a href="settings" class="dropdown-link">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                            </svg>
                                            <span>Настройки</span>
                                        </a></li>
                                        <li><a id="logoutButton" href="" class="dropdown-link logout">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                                            </svg>
                                            <span>Выйти</span>
                                        </a></li>
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
        <?php if ($layout === Layout::Standart && !empty($currentUserId)): ?>
            <div class="navigation-menu">
                <nav class="navigation">
                    <ul>
                        <li><a href="<?= $currentUser_link ?>">Профиль</a></li>
                        <li><a href="feed">Лента</a></li>
                        <li><a href="messages">Сообщения</a></li>
                        <li><a href="contacts">Контакты</a></li>
                        <li><a href="groups">Группы</a></li>
                        <li><a href="search">Поиск</a></li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
        
        <div class="page-content">
            <?php
                if (isset($content)) {
                    echo $content;
                }
            ?>
        </div>
    </main>

    <?php if ($layout !== Layout::Micro): ?>
        <footer>
            &copy; NNN.
        </footer>
        
    <?php endif; ?>

    <?php if (!empty($currentUserId)): ?>
        <script src="<?= JS_URL ?>/layout.js" type="module"></script>
    <?php endif; ?>

</body>
</html>