<?php
    require_once 'includes/init.php';
    
    require_once 'enums/auth.php';
    require_once 'enums/layout.php';
    if (empty($layout)) {
        $layout = Layout::Standart;
    }

    if ($layout === Layout::Standart) {
        global $db, $current_user_id, $current_user;
        if ($current_user_id && $current_user) {
            $current_user_fullname = $current_user['firstname'] . ' ' . $current_user['lastname'];
            $current_user_link = empty($current_user['linkname']) ? 'user' . $current_user_id : $current_user['linkname'];
            $current_user_photo = $current_user['photo'];

            # Получаем статус пользователя
            $stmt = $db->prepare('SELECT * FROM user_statuses WHERE id = ?');
            $stmt->bindValue(1, $current_user['status_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $current_user_status = $result->fetchArray(SQLITE3_ASSOC) ?: null;

            # Получаем роль пользователя
            $stmt = $db->prepare('SELECT * FROM user_roles WHERE id = ?');
            $stmt->bindValue(1, $current_user['role_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $current_user_role = $result->fetchArray(SQLITE3_ASSOC) ?: null;
        }
    }

    // Полная ссылка
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    $return_url = urlencode($protocol . '://' . $host . $path);
?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
            $base_title = 'NNN';
            if (!empty($title)) {
                echo $title;
            } else {
                echo $base_title;
            }
        ?>
    </title>

    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/global.css">

    <!-- Подгружаем стиль определённой страницы -->
    <?php
        if (isset($stylesheets) && $stylesheets > 0) {
            foreach ($stylesheets as $sh) {
                echo "<link rel=\"stylesheet\" href=$sh>";
            }
        }
        if (!empty($stylesheet)) {
            echo "<link rel=\"stylesheet\" href=$stylesheet>";
        }
    ?>
</head>
<body class="layout-<?= strtolower($layout->name) ?>">
    <?php if ($layout !== Layout::Micro): ?>
        <header>
            <div class="header-container">
                <a href="/" class="logo-row">
                    <img src="../images/logo.png" alt="Логотип NNN" class="logo">
                    <h1>NNN</h1>
                </a>
                <?php if ($layout === Layout::Standart): ?>
                    <div class="header-row">
                        <?php if (empty($current_user_id)): ?>
                            <nav class="auth">
                                <ul>
                                    <li><a href="<?= Auth::Login->text(); ?>?return_url=<?= $return_url ?>" class="inline">Войти</a></li>
                                    <li><a href="<?= Auth::Register->text(); ?>?return_url=<?= $return_url ?>" class="inline">Регистрация</a></li>
                                </ul>
                            </nav>

                        <?php else: ?>
                            <nav class="profile-dropdown">
                                <button class="profile-trigger" aria-label="Меню профиля">
                                    <div class="profile-avatar">
                                        <img src="<?= $current_user_photo ?: 'images/empty.webp' ?>" alt="<?= htmlspecialchars($current_user_fullname) ?>" width="32" height="32">
                                    </div>
                                    <span class="profile-name"><?= htmlspecialchars($current_user_fullname) ?></span>
                                    <svg class="dropdown-arrow" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                        <path d="M7 10l5 5 5-5z"/>
                                    </svg>
                                </button>
                                
                                <div class="dropdown-menu">
                                    <div class="profile-info">
                                        <img src="<?= $current_user_photo ?: 'images/empty.webp' ?>" alt="<?= htmlspecialchars($current_user_fullname) ?>" width="48" height="48">
                                        <div>
                                            <div class="profile-fullname"><?= htmlspecialchars($current_user_fullname) ?></div>
                                            <div class="profile-link">@<?= htmlspecialchars($current_user['linkname'] ?: 'user' . $current_user_id) ?></div>
                                        </div>
                                    </div>
                                    <ul class="dropdown-list">
                                        <li><a href="<?= $current_user_link ?>" class="dropdown-link">
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
                                        <li><a href="actions/session/logout.php" class="dropdown-link logout">
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
        <?php if ($layout === Layout::Standart && !empty($current_user_id)): ?>
            <div class="navigation-menu">
                <nav class="navigation">
                    <ul>
                        <li><a href="<?= $current_user_link ?>">Профиль</a></li>
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

    <?php if (!empty($current_user_id)): ?>
        <script src="js/layout.js"></script>
    <?php endif; ?>

</body>
</html>