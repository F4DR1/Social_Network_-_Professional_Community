<?php
    // feed.php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    require_once INCLUDES_PATH . '/elements.php';
    global $currentUserId;
    
    ob_start();
?>



<div class="centered-container">
    <?= postCreationField(); ?>
    <?= postsPanel('Лента новостей'); ?>
</div>
    
    
<script>
    window.appData = <?= json_encode([
        'postsType' => 'feed'
    ]) ?>;
</script>
<script>
    window.appData = <?= json_encode([
        'path' => PATH,
        'currentUserId' => $currentUserId,
        'postsType' => 'feed'
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Лента новостей';
    $scripts = [
        'elements/files_upload.js',
        'elements/posts.js'
    ];
    $stylesheets = [
        'elements/files_upload.css',
        'elements/posts.css',
        'elements/post_create.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
