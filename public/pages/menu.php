<?php
    // feed.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/elements.php';
    global $currentUser;
    
    // Получаем данные пользователя
    if (!empty($currentUser)) {
        $profileLink = $currentUser['linkname'] ?? 'user' . $currentUser['id'];
    }
    
    ob_start();
?>



<div class="centered-container">
    <div class="container menu">
        <nav class="navigation">
            <ul>
                <!-- Кнопки навигации -->
                <li>
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M5.5 19a7 7 0 0 1 13 0"/>
                    </svg>
                    <a href="<?= $profileLink ?>">Профиль</a>
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
                <li>
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="11" r="8"/>
                        <line x1="12" y1="15" x2="12" y2="11"/>
                        <line x1="12" y1="7" x2="12.01" y2="7"/>
                    </svg>
                    <a href="about">О нас</a>
                </li>
            </ul>
        </nav>
    </div>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Меню';
    $scripts = [
        'elements/menu.js'
    ];
    $stylesheets = [
        'elements/menu.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
