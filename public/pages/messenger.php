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
    <section class="container">

        <section class="chats-window">
            <div class="chats-header">
                <h2 class="container-title">Мессенджер</h2>
            </div>
            <div class="chats-list" id="chatsList">
                <!-- Здесь все чаты пользователя -->
            </div>
        </section>
        <section class="messages-window">
            <div class="no-selected-chat" id="noChatPanel">
                <!-- Чат ещё не выбран -->
            </div>
            <div class="selected-chat" id="chatPanel">
                <div class="chat-info" id="chatInfo">
                    <!-- Верхняя панель (с информацией о чате) -->
                    <button class="chat-info-back-btn" id="chatBackButton">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="27" y1="12" x2="3" y2="12"/>
                            <polyline points="10,19 3,12 10,5"/>
                        </svg>
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
                    <!-- Нижняя панель (поле ввода сообщения и статус печатания) -->
                    <span class="typing-message" id="typingMessage"></span>
                    <div class="message-input-line input-field">
                        <textarea min="1" max="4096" type="text" id="messageInput" required placeholder="Сообщение"></textarea>
                        <button class="message-send-btn" id="sendMessageButton">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12,5 19,12 12,19"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </section>
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
