<?php
    // feed.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUserId;
    
    ob_start();
?>



<div class="centered-container">
    <section class="container data">
        <h2 class="container-title">О нас</h2>
        
        <p>Связаться с нами: <a href="mailto:support@мирпрофи.рф">support@мирпрофи.рф</a></p>
    </section>
</div>



<?php
    $content = ob_get_clean();
    $title = 'О нас';
    $scripts = [
        'pages/about.js'
    ];
    $stylesheets = [
        'pages/about.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
