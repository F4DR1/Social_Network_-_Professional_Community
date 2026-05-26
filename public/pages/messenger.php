<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once INCLUDES_PATH . '/init.php';
    global $currentUser;
    
    
    $chatSubType = $_GET['type'] ?? '';
    $chatSubId = $_GET['id'] ?? '';

    $openedChatId = null;

    ob_start();
?>



<div class="centered-container">
    <div class="container">

        <section class="chats-window">
            <div class="chats-header">
                <h2>Мессенджер</h2>
            </div>
            <div class="chats-list" id="chatsList">
                <!-- Здесь все чаты -->
            </div>
        </section>
        <section class="messages-window">
            <div class="no-selected-chat" id="noChatPanel">
                <!-- Здесь список чатов пользователя -->
            </div>
            <div class="selected-chat" id="chatPanel">
                <div class="chat-info" id="chatInfo">
                    <button class="chat-info-back-btn" id="chatBackButton">
                        ⬅️
                    </button>
                    <img class="chat-info-image" src="" alt="" width="50" height="50">
                    <div class="chat-info-main">
                        <span class="chat-info-title"></span>
                        <span class="chat-info-members-count"></span>
                    </div>
                </div>
                <div class="messages" id="chatMessages">
                    <!-- Здесь все сообщения выбранного чата (отображается только когда чат выбран) -->
                </div>
                <div class="message-iput">
                    <span class="typing-message" id="typingMessage"></span>
                    <div class="message-input-line input-field">
                        <textarea min="1" max="4096" type="text" id="messageInput" required placeholder="Сообщение"></textarea>
                        <button class="message-send-btn" id="sendMessageButton">
                            ➡️
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>


<script>
    window.appData = <?= json_encode([
        'currentUser' => $currentUser,
        'chatSubjectType' => $chatSubType ?: null,
        'chatSubjectId' => $chatSubId ?: null
    ]) ?>;
</script>



<?php
    $content = ob_get_clean();
    $title = 'Сообщения';
    $scripts = [
        'pages/messenger.js'
    ];
    $stylesheets = [
        'pages/messenger.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Standart;
    require ROOT_PATH . '/layout.php';
?>
