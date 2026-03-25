<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/elements.php';
    
    ob_start();
?>



<div class="centered-container">
    <?= postCreationField(); ?>
    <?= postsPanel('Лента'); ?>
</div>
    
    
<script>
    window.appData = <?= json_encode([
        'postsType' => 'feed'
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Лента новостей';
    $scripts = [
        'posts.js'
    ];
    $stylesheets = [
        'elements/post_create.css',
        'elements/post.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
