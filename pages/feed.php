<?php
    require_once '../includes/init.php';
    global $db_frontend, $current_user_id;
    
    if (!$current_user_id) {
        $return_url = urlencode($_SERVER['REQUEST_URI']);
        header("Location: /auth.php?return_url=" . $return_url);
        exit;
    }
    
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
    require_once '../enums/layout.php';
    $layout = Layout::Standart;
    require '../layout.php';
?>
