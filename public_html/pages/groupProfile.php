<?php
    require_once 'includes/init.php';
    global $db, $current_user_id;


    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    $action = $_GET['act'] ?? '';

    // Отформатированные данные группы
    $group_name = $group['name'];
    $group_photo = $group['photo'];


    
    // Проверяем админ ли
    $is_admin = false;

    $stmt = $db->prepare('SELECT id FROM group_roles WHERE name = ?');
    $stmt->bindValue(1, 'owner', SQLITE3_TEXT);
    $result = $stmt->execute();
    $role = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($role) {
        $owner_role_id = $role['id'];

        // Проверка прав пользователя
        $stmt = $db->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? AND role_id = ?');
        $stmt->bindValue(1, $group['id'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
        $stmt->bindValue(3, $owner_role_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        if ($result->fetchArray(SQLITE3_ASSOC) !== false) {
            // Пользователь админ
            $is_admin = true;
        }
    }


    if (empty($action)) {
        
    } elseif ($action === 'edit') {
        if (!$is_admin) header('Location: ' . $path);
    }

    
    ob_start();
?>



<?php if (empty($action)): ?>
    <div class="centered-container">
        <div class="container main-container">
            <img src="<?= $group_photo ?? 'images/empty.webp' ?>" alt="<?= htmlspecialchars($group_name) ?>" width=200>
            <h1><?= htmlspecialchars($group_name) ?></h1>
            <p><?= $action ?></p>
            
            <div class="profile-actions-panel">
                <!-- Главная кнопка -->
                <div class="main-action">
                    <div id="mainRequestSubscribe">
                        <button class="standart-btn" id="subscribeButton">
                            <span>Вступить в группу</span>
                        </button>
                    </div>
                    <div id="mainRequestUnsubscribe">
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
                    </div>
                </div>

                <!-- Второстепенная -->
                <div class="second-action">
                    <div id="secondMessage">
                        <a href="messages?type=group&id=<?= $group['id'] ?>" class="btn">Написать в чат</a>
                    </div>
                </div>

            </div>
        </div>

        <div class="container">
            <h2>Новый пост</h2>
            <div class="new-post">
                <div class="input-field">
                    <textarea min="1" max="500" type="text" id="newPostText" required placeholder="Напишите что-нибудь..."></textarea>
                </div>
                <button id="postNewPost">Опубликовать</button>
            </div>
        </div>

        <div class="container">
            <h2>Посты</h2>
            <div class="posts" id="postsList">

                <!-- <div class="post">
                    <div class="post-head">
                        <a class="post-author">
                            <img src="" alt="Тестовая группа" width="40" height="40">
                            <p>Тестовая группа</p>
                        </a>
                        <div class="post-actions">
                            <div class="action-dropdown">
                                <button class="standart-btn action-trigger">
                                    <span>...</span>
                                </button>
                                <ul class="dropdown-list">
                                    <li>
                                        <button class="dropdown-button" id="deleteButton">
                                            <span>Удалить пост</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <p class="text-content">Некоторый текст</p>
                    </div>

                    <div class="post-footer">
                        <a class="group-post-author">
                            От Окладников Даниил
                        </a>
                        <p class="post-date">14.02.2026</p>
                    </div>
                </div> -->
                
            </div>
        </div>
    </div>

    <div class="right-container">
        <?php if ($is_admin): ?>
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
            'groupId' => $group['id'],
            'currentUserId' => $current_user_id
        ]) ?>;
    </script>
    <script src="js/group_profile.js"></script>
    <script src="js/profile_dropdown.js"></script>
    <script src="js/posts.js"></script>

<?php elseif ($action === 'edit'): ?>
    <div class="centered-container">
        <div class="container">
            <h2>Основная информация</h2>

            <div id="baseInfoSuccess">
                <!-- SVG Слева Зелёная галочка в круге -->
                <h3>Изменения сохранены</h3>  <!-- Верхняя строка -->
                <p>Основная информация группы сохранена.</p>  <!-- Нижняя строка -->
            </div>
            <div id="baseInfoError">
                <!-- SVG Слева Красный крест в круге -->
                <h3>Ошибка при сохранении</h3>  <!-- Верхняя строка -->
                <p id="errorMessage"></p>  <!-- Нижняя строка -->
            </div>

            <div class="input-field">
                <span>Название:</span>
                <input type="text" id="groupName" value="<?= htmlspecialchars($group['name']) ?>" name="name" required autocomplete="name">
            </div>
            <div class="input-field">
                <span>Адрес:</span>
                <span class="included"><?= htmlspecialchars($protocol . '://' . $host . '/') ?></span>
                <input type="text" id="groupLinkname" value="<?= htmlspecialchars($group['linkname'] ?? 'group' . $group['id']) ?>" name="name" required autocomplete="name">
                <span class="hint">Номер группы — <u><?= htmlspecialchars('group' . $group['id']) ?></u>.</span>
            </div>

            <button class="standart-btn" id="saveBaseInfo">Сохранить</button>
        </div>
    </div>

    <div class="right-container">
        <div class="container">
            <a class="btn" id="groupPath">
                <img src="<?= $group_photo ?? 'images/empty.webp' ?>" alt="<?= htmlspecialchars($group_name) ?>" width=60>
                <h1><?= htmlspecialchars($group_name) ?></h1>
                <p>вернуться к странице</p>
            </a>
        </div>
    </div>
    
    
    <script>
        window.appData = <?= json_encode([
            'groupPath' => $group['linkname'] ?? 'group' . $group['id'],
            'groupId' => $group['id'],
            'currentUserId' => $current_user_id
        ]) ?>;
    </script>
    <script src="js/group_profile_edit.js"></script>
    <script src="js/profile_dropdown.js"></script>

<?php endif; ?>



<?php
    $content = ob_get_clean();
    $title = $group_name;

    
    if (empty($action)) {
        $stylesheets = [
            'css/group_profile.css',
            'css/posts.css'
        ];
    } elseif ($action === 'edit') {
        $stylesheets = [
            'css/group_profile_edit.css'
        ];
    }
    
    
    require_once 'enums/layout.php';
    $layout = Layout::Standart;

    require 'layout.php';
?>
