import {
    authLogin, authRegister, authRegisterDataValidate,
    codesSend, codesConfirm
} from '../api.js';

document.addEventListener("DOMContentLoaded", () => {
    const authContainer = document.getElementById('authContainer');

    // Элементы контейнера
    const authTitle = document.getElementById('authTitle');
    const authSubtitle = document.getElementById('authSubtitle');
    const authTabs = document.getElementById('authTabs');
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');

    const loginForm = document.getElementById('loginForm');
    const loginMessage = document.getElementById('loginMessage');

    const registerForm = document.getElementById('registerForm');
    const registerMessage = document.getElementById('registerMessage');

    const recoveryForm = document.getElementById('recoveryForm');
    const recoveryMessage = document.getElementById('recoveryMessage');

    const codeForm = document.getElementById('codeForm');
    const codeMessage = document.getElementById('codeMessage');

    const successScreen = document.getElementById('successMessage');
    
    const resendCodeTimer = document.getElementById('resendCodeTimer');
    const resendCodeLine = document.getElementById('resendCodeLine');
    const resendCodeBtn = document.getElementById('resendCodeBtn');
    


    // Элементы логина
    const loginLogin = document.getElementById('loginLogin');
    const loginPassword = document.getElementById('loginPassword');

    // Элементы регистрации
    const registerLogin = document.getElementById('registerLogin');
    const registerPassword = document.getElementById('registerPassword');
    const registerFirstname = document.getElementById('registerFirstname');
    const registerLastname = document.getElementById('registerLastname');
    
    // Элементы восстановления
    const recoveryLogin = document.getElementById('recoveryLogin');
    
    // Элементы кода
    const codeInput = document.getElementById('codeInput');


    
    let sentCodePurpose = null;
    let codeSentMessage = '';





    // -------------------- ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ --------------------
    // Обработка Enter
    function setupEnterKeySubmit() {
        // Функция для получения активной формы
        function getActiveForm() {
            return document.querySelector('.auth-form.active');
        }
        
        // Функция для получения кнопки активной формы
        function getActiveFormButton() {
            const activeForm = getActiveForm();
            if (activeForm) {
                return activeForm.querySelector('.submit-btn');
            }
            return null;
        }
        
        // Обработчик нажатия клавиши
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                // Предотвращаем стандартное поведение (особенно важно для формы регистрации)
                event.preventDefault();
                
                // Получаем активную кнопку
                const activeButton = getActiveFormButton();
                
                // Если кнопка существует и форма активна, нажимаем её
                if (activeButton) {
                    // Проверяем, что нажатие было в поле ввода
                    if (event.target.tagName === 'INPUT') {
                        // Добавляем небольшую задержку для лучшего UX
                        setTimeout(() => {
                            activeButton.click();
                        }, 50);
                    }
                }
            }
        }
        
        // Добавляем обработчики ко всем полям ввода
        const allInputs = document.querySelectorAll('.auth-form input');
        allInputs.forEach(input => {
            input.addEventListener('keypress', handleKeyPress);
        });
        
        // Также добавляем обработчик для динамически переключаемых форм
        const observer = new MutationObserver(() => {
            // При переключении форм обновляем обработчики
            const newInputs = document.querySelectorAll('.auth-form.active input');
            newInputs.forEach(input => {
                input.removeEventListener('keypress', handleKeyPress);  // Убираем старый обработчик если есть
                input.addEventListener('keypress', handleKeyPress);  // Добавляем новый
            });
        });
        
        // Наблюдаем за изменениями в формах
        const formsContainer = document.querySelector('.forms-container');
        if (formsContainer) {
            observer.observe(formsContainer, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }
    }

    // Фокус на активную форму
    function focusActiveForm() {
        switch (authContainer.dataset.currentForm) {
            case 'login':
                if (loginLogin) loginLogin.focus();
                break;

            case 'register':
                if (registerLogin) registerLogin.focus();
                break;

            case 'recovery':
                if (recoveryLogin) recoveryLogin.focus();
                break;
        
            default:
                break;
        }
    }

    // Показ/скрытие сообщений
    function setMessage(message, text, type = 'error') {
        const textStr = String(text || '');
        message.textContent = textStr;
        if (textStr.trim() !== '') {
            message.className = `message show ${type}`;
        } else {
            message.className = 'message';
        }
    }

    // Очистка сообщений на форме
    function clearMessages() {
        authContainer.querySelectorAll('.message').forEach(msg => {
            msg.textContent = '';
            msg.className = 'message';
        });
    }

    // Таймер повторной отправки кода
    function startResendTimer(seconds = 60) {
        if (!resendCodeTimer || !resendCodeLine) return;
    
        // Показываем таймер, скрываем кнопку для повторной отправки
        resendCodeTimer.classList.add('active');
        resendCodeLine.classList.remove('active');
        resendCodeBtn.disabled = true;
        
        let remaining = seconds;
        
        // Функция форматирования времени
        const formatTime = (sec) => {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        };
        
        // Сразу обновляем текст
        resendCodeTimer.textContent = `Повторно отправить код можно через ${formatTime(remaining)}`;
        
        const intervalId = setInterval(() => {
            remaining--;
            if (remaining > 0) {
                resendCodeTimer.textContent = `Повторно отправить код можно через ${formatTime(remaining)}`;
            } else {
                clearInterval(intervalId);
                resendCodeTimer.classList.remove('active');
                resendCodeLine.classList.add('active');
                resendCodeBtn.disabled = false;
            }
        }, 1000);
    }





    // -------------------- ВИЗУАЛЬНАЯ ОБРАБОТКА --------------------
    function switchForm(formName) {
        // Сначала скрываем текущую форму
        const currentActive = authContainer.querySelector('.auth-form.active');
        if (currentActive) {
            currentActive.style.opacity = '0';
            currentActive.classList.remove('active');
        }

        // Очищаем поля сообщений
        clearMessages();


        let targetForm;
        let formTitle = '';
        let formSubtitle = '';
        switch (formName) {
            case 'register-code':
            case 'recovery-code':
                authTabs.classList.toggle('active', false);
                
                if (currentActive) currentActive.style.transform = 'translate(20px, 0)';
                
                // Обновляем табы
                loginTab.classList.toggle('active', false);
                registerTab.classList.toggle('active', false);

                targetForm = codeForm;
                formTitle = 'Введите код подтверждения';
                formSubtitle = codeSentMessage;
                break;

            case 'recovery':
                sentCodePurpose = null;
                codeSentMessage = '';

                document.title = 'Восстановление доступа';
                authTabs.classList.toggle('active', false);
                
                if (currentActive) currentActive.style.transform = 'translate(20px, 0)';
                
                // Обновляем табы
                loginTab.classList.toggle('active', false);
                registerTab.classList.toggle('active', false);

                targetForm = recoveryForm;
                formTitle = 'Восстановление доступа';
                formSubtitle = 'Введите данные для восстановления';
                break;

            case 'login':
            case 'register':
                sentCodePurpose = null;
                codeSentMessage = '';

                const isLogin = formName === 'login';

                document.title = isLogin ? 'Авторизация' : 'Регистрация';
                authTabs.classList.toggle('active', true);
                
                if (currentActive) currentActive.style.transform = isLogin ? 'translate(20px, 0)' : 'translate(-20px, 0)';
                
                // Обновляем табы
                loginTab.classList.toggle('active', isLogin);
                registerTab.classList.toggle('active', !isLogin);

                targetForm = isLogin ? loginForm : registerForm;
                formTitle = isLogin ? 'Вход в аккаунт' : 'Создать аккаунт';
                formSubtitle = isLogin ? 'Введите данные для входа' : 'Присоединяйтесь к сети';
                break;
        
            default:
                break;
        }

        
        // Задержка для плавного перехода
        setTimeout(() => {
            // Обновляем заголовок и описание формы
            authTitle.textContent = formTitle;
            authSubtitle.textContent = formSubtitle;

            // Показываем новую форму
            targetForm.classList.add('active');
            targetForm.style.opacity = '1';
            targetForm.style.transform = 'translate(0, 0)';
        }, 200);


        // Фокус на нужное поле
        setTimeout(focusActiveForm, 250);
    }



    

    // -------------------- API --------------------
    // Авторизация
    async function loginAPI(message, login = null, password = null) {
        const data = {
            login: login?? loginLogin.value,
            password: password ?? loginPassword.value
        };

        try {
            const result = await authLogin(data);

            if (result.success) {
                clearMessages();
                loginForm.classList.remove('active');
                registerForm.classList.remove('active');
                recoveryForm.classList.remove('active');
                codeForm.classList.remove('active');
                successScreen.classList.add('active');
                setTimeout(() => (window.location.href = authContainer.dataset.returnUrl), 2000);

            } else {
                setMessage(message, result.error || 'Ошибка авторизации', 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }

    // Регистрация
    async function registerAPI(message) {
        const data = {
            phone: registerLogin.value,
            password: registerPassword.value,
            firstname: registerFirstname.value,
            lastname: registerLastname.value
        };

        try {
            const result = await authRegister(data);

            if (result.success) {
                clearMessages();
                await loginAPI(message, data.phone, data.password);

            } else {
                setMessage(message, result.error || 'Ошибка регистрации', 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }

    // Проверить доступность данных для регистрации
    async function validateRegisterDataAPI(message) {
        const data = {
            phone: registerLogin.value,
            password: registerPassword.value,
            firstname: registerFirstname.value,
            lastname: registerLastname.value
        };

        try {
            const result = await authRegisterDataValidate(data);

            if (result.success) {
                clearMessages();
                sendCodeAPI(registerMessage);

            } else {
                setMessage(message, result.error || 'Ошибка регистрации', 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }

    // Отправить код
    async function sendCodeAPI(message) {
        const currentForm = authContainer.dataset.currentForm;

        const data = {};
        
        let newForm = '';
        switch (currentForm) {
            case 'recovery':
                newForm = 'recovery-code';
                data.purpose = 'recovery_verification';
                data.login = recoveryLogin.value;
                break;
            
            case 'register':
                newForm = 'register-code';
                data.purpose = 'register_verification';
                data.login = registerLogin.value;
                break;
        
            default:
                return;
        }

        try {
            const result = await codesSend(data);

            if (result.success) {
                startResendTimer();
                // Переключаем форму на ввод кода
                codeSentMessage = `${result.message} Код: ${result.code}. `;  // Временно выводим код на фронтенде (отправки по телефону/email пока нет)
                sentCodePurpose = data.purpose;
                switchForm(newForm);

            } else {
                setMessage(message, result.error || 'Ошибка регистрации', 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }

    // Проверить код
    async function confirmCodeAPI(message) {
        const data = {
            purpose: sentCodePurpose,
            code: codeInput.value
        };
        if (sentCodePurpose == 'recovery_verification') data.login = recoveryLogin.value;
        else if (sentCodePurpose == 'register_verification') data.login = registerLogin.value;

        try {
            const result = await codesConfirm(data);

            if (result.success) {
                switch (sentCodePurpose) {
                    case 'register_verification':
                        setMessage(codeMessage, result.message, 'success');
                        registerAPI(registerMessage);
                        break;
                        
                    case 'recovery_verification':
                        clearMessages();
                        loginForm.classList.remove('active');
                        registerForm.classList.remove('active');
                        recoveryForm.classList.remove('active');
                        codeForm.classList.remove('active');
                        successScreen.classList.add('active');
                        setTimeout(() => (window.location.href = authContainer.dataset.returnUrl), 2000);
                        break;
                
                    default:
                        return;
                }
            } else {
                setMessage(message, result.error || 'Ошибка регистрации', 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }


    

    
    // -------------------- МЕТОДЫ --------------------
    // Обработка клика по вкладкам
    function tabClickHandler(formName) {
        if (formName != 'login' && formName != 'register' && formName != 'recovery') return;

        const oldFormName = authContainer.dataset.currentForm;
        authContainer.dataset.currentForm = formName;

        // Устанавливаем новый адрес (без поддержки истории)
        const currentPath = window.location.pathname;
        const segments = currentPath.split('/');
        segments[segments.length - 1] = formName;
        const newPath = segments.join('/');
        window.history.replaceState({}, '', newPath);

        // Переключаем формы
        switchForm(formName);
    }





    // -------------------- СТАРТОВАЯ ОБРАБОТКА --------------------
    // Добавляем событие клика по вкладке
    loginTab.addEventListener('click', () => tabClickHandler('login'));
    registerTab.addEventListener('click', () => tabClickHandler('register'));

    // Устанавливаем стартовую вкладку
    tabClickHandler(authContainer.dataset.currentForm);

    // Обработка клавиши Enter на вкладках
    setupEnterKeySubmit();





    // -------------------- КНОПКИ ПЕРЕХОДА К ФОРМАМ --------------------
    // Кнопка возврата к форме восстановления
    document.getElementById('codeBackBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        tabClickHandler('recovery');
    });

    // Кнопка возврата к форме входа
    document.getElementById('recoveryBackBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        tabClickHandler('login');
    });

    // Кнопка перехода к форме восстановления
    document.getElementById('showRecoveryFormBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        tabClickHandler('recovery');
    });

    // Кнопка регистрации
    document.getElementById('registerBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        // Отправить код пользователю
        clearMessages();
        validateRegisterDataAPI(registerMessage);
    });

    

    // -------------------- КНОПКИ API ДЕЙСТВИЙ --------------------
    // Кнопка отправки подтверждения кода
    document.getElementById('confirmCodeBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        // Отправить код на проверку
        clearMessages();
        confirmCodeAPI(codeMessage);
    });

    // Кнопка повторной отправки кода
    resendCodeBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        // Отправить код пользователю
        clearMessages();
        sendCodeAPI(codeMessage);
    });

    // Кнопка отправки кода и перехода к форме кода
    document.getElementById('sendRecoveryCodeBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        // Отправить код пользователю
        clearMessages();
        sendCodeAPI(recoveryMessage);
    });

    // Кнопка авторизации
    document.getElementById('loginBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        // Авторизовываем
        clearMessages();
        await loginAPI(loginMessage);
    });
});
