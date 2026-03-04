<?php
    require_once '../includes/init.php';
    
    if (empty($GLOBALS['current_user_id'])) {
        header('Location: /');
        exit;
    }

    $title = 'Лента новостей';
    
    ob_start();
?>



<div class="centered-container">
    <div class="container">
        <h2>Лента</h2>
    </div>
</div>



<?php
    $content = ob_get_clean();
    
    require_once '../enums/layout.php';
    $layout = Layout::Standart;

    require '../layout.php';
?>
