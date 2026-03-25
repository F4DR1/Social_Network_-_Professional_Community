<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once ENUMS_PATH . '/auth.php';

    $form = $_GET['form'] ?? '';
    $returnUrl = $_GET['return_url'] ?? BASE_URL;

    $isRegister = $form === Auth::Register->text() ? true : false;
    
    ob_start();
?>



<div class="auth-container" data-current-form="<?= $isRegister ? 'register' : 'login' ?>" data-return-url="<?= htmlspecialchars($returnUrl) ?>">
    <div class="auth-panel">
        <!-- Заголовок -->
        <div class="auth-header">
            <h2 id="authTitle"></h2>
            <p id="authSubtitle"></p>
        </div>
        
        <!-- Переключатели -->
        <div class="auth-tabs">
            <button class="tab-btn <?= $isRegister ? '' : 'active' ?>" data-form="login">
                <svg class="tab-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
                Вход
            </button>
            <button class="tab-btn <?= $isRegister ? 'active' : '' ?>" data-form="register">
                <svg class="tab-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                Регистрация
            </button>
        </div>

        <!-- Формы -->
        <div class="forms-container">
            <!-- Форма логина -->
            <div id="loginForm" class="auth-form <?= $isRegister ? '' : 'active' ?>">
                <div class="form-fields">
                    <div class="input-field">
                        <input type="text" id="login" name="login" required autocomplete="username">
                        <label>Телефон или email</label>
                    </div>
                    <div class="input-field">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <label>Пароль</label>
                    </div>
                </div>
                <div id="loginMessage" class="message"></div>
                <button class="submit-btn" id="loginBtn">
                    <span class="btn-text">Войти</span>
                </button>
            </div>

            <!-- Форма регистрации -->
            <div id="registerForm" class="auth-form <?= $isRegister ? 'active' : '' ?>">
                <div class="form-fields">
                    <div class="input-field">
                        <input type="text" id="regLogin" name="login" inputmode="numeric" pattern="^\+7[1-9]{10}$" required autocomplete="username">
                        <label class="required">Телефон</label>
                    </div>
                    <div class="input-field">
                        <input type="password" id="regPassword" name="password" required autocomplete="new-password" minlength="6">
                        <label class="required">Пароль</label>
                    </div>
                    <div class="input-field">
                        <input type="text" id="regFirstname" name="firstname" required autocomplete="given-name">
                        <label class="required">Имя</label>
                    </div>
                    <div class="input-field">
                        <input type="text" id="regLastname" name="lastname" required autocomplete="family-name">
                        <label class="required">Фамилия</label>
                    </div>
                </div>
                <div id="registerMessage" class="message"></div>
                <button class="submit-btn" id="registerBtn">
                    <span class="btn-text">Зарегистрироваться</span>
                </button>
            </div>
        </div>

        <!-- Успех -->
        <div id="successMessage" class="success-screen">
            <div class="success-icon">✅</div>
            <h3>Успешный вход</h3>
            <p id="successText">Добро пожаловать!</p>
        </div>
    </div>
</div>



<script>
    const returnUrl = "<?= htmlspecialchars($returnUrl) ?>";
</script>



<?php
    $content = ob_get_clean();
    $title = $isRegister ? 'Регистрация' : 'Авторизация';
    $scripts = [
        'auth.js'
    ];
    $stylesheets = [
        'pages/auth.css',
        'elements/input_field.css'
    ];
    require_once ENUMS_PATH . '/layout.php';
    $layout = Layout::Mini;
    require ROOT_PATH . '/layout.php';
?>
