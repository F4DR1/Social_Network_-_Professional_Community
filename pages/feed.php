<?php
    require_once __DIR__ . '/../bootstrap.php';
    
    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Лента</h2>
    </div>
</div>



<?php
    $content = ob_get_clean();
    $title = 'Лента новостей';
    $scripts = [];
    $stylesheets = [];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
