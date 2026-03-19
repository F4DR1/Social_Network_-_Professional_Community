import { authLogin, authRegister } from './api.js';

document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".auth-container");
    const loginTab = document.querySelector('[data-form="login"]');
    const registerTab = document.querySelector('[data-form="register"]');

    const title = document.getElementById("authTitle");
    const subtitle = document.getElementById("authSubtitle");
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");
    const successScreen = document.getElementById("successMessage");



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

    function clearMessages() {
        document.querySelectorAll('.message').forEach(msg => {
            msg.textContent = '';
            msg.className = 'message';
        });
    }

    // Переключатель форм
    function switchForm(formName) {
        // Сначала скрываем текущую форму
        const currentActive = document.querySelector('.auth-form.active');
        if (currentActive) {
            currentActive.style.opacity = '0';
            currentActive.style.transform = formName === 'login' ? 'translateX(20px)' : 'translateX(-20px)';
            currentActive.classList.remove('active');
        }

        clearMessages();
        
        // Обновляем табы
        loginTab.classList.toggle("active", formName === "login");
        registerTab.classList.toggle("active", formName === "register");
        container.dataset.currentForm = formName;

        
        // Задержка для плавного перехода
        setTimeout(() => {
            const targetForm = formName === 'login' ? loginForm : registerForm;
            
            // Показываем новую форму
            targetForm.classList.add('active');
            targetForm.style.opacity = '1';
            targetForm.style.transform = 'translateX(0)';

            // Обновляем заголовок и описание формы
            
            document.title = formName === 'login' ? 'Авторизация' : 'Регистрация';
            title.textContent = formName === 'login' ? 'Вход в аккаунт' : 'Создать аккаунт';
            subtitle.textContent = formName === 'login' ? 'Введите данные для входа' : 'Присоединяйтесь к сети';
        }, 200);

        // Фокус на нужное поле
        setTimeout(focusActiveForm, 250);
    }

    function focusActiveForm() {
        const currentForm = container.dataset.currentForm;
        if (currentForm === 'login') {
            const loginInput = document.getElementById('login');
            if (loginInput) loginInput.focus();
        } else if (currentForm === 'register') {
            const regLoginInput = document.getElementById('regLogin');
            if (regLoginInput) regLoginInput.focus();
        }
    }



    // Инициализация
    loginTab.addEventListener("click", () => switchForm("login"));
    registerTab.addEventListener("click", () => switchForm("register"));
    switchForm(container.dataset.currentForm);
    setupEnterKeySubmit();



    // Логин
    async function loginAPI(message, loginStr, passwordStr) {
        const data = {
            login: loginStr,
            password: passwordStr
        };

        try {
            const result = await authLogin(data);

            if (result.success) {
                clearMessages();
                loginForm.classList.remove("active");
                registerForm.classList.remove("active");
                successScreen.classList.add("active");
                setTimeout(() => (window.location.href = container.dataset.returnUrl), 2000);

            } else {
                setMessage(message, result.error || "Ошибка авторизации", 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }

    // Регистрация
    async function registerAPI(message) {
        const data = {
            phone: document.getElementById("regLogin").value,
            password: document.getElementById("regPassword").value,
            firstname: document.getElementById("regFirstname").value,
            lastname: document.getElementById("regLastname").value
        };

        try {
            const result = await authRegister(data);

            if (result.success) {
                clearMessages();
                await loginAPI(message, data.phone, data.password);

            } else {
                setMessage(message, result.error || "Ошибка регистрации", 'error');
            }

        } catch (err) {
            setMessage(message, err, 'error');
        }
    }



    // === ЛОГИН ===
    document.getElementById("loginBtn").addEventListener("click", async (e) => {
        e.preventDefault();
        clearMessages();
        const login = document.getElementById("login").value;
        const password = document.getElementById("password").value;
        const message = document.getElementById("loginMessage");
        await loginAPI(message, login, password);
    });

    // === РЕГИСТРАЦИЯ ===
    document.getElementById("registerBtn").addEventListener("click", async (e) => {
        e.preventDefault();
        clearMessages();
        const message = document.getElementById("registerMessage");
        await registerAPI(message);
    });
});
