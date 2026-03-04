document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = window.appData.currentUserId;

    const message = document.getElementById('errorMessage');
    const modal = document.getElementById('modal');


    // Показать модальное окно
    function showModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';  // Блокируем скролл
    }

    // Скрыть модальное окно
    function hideModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';  // Возвращаем скролл
    }

    // Показать сообщение
    function setMessage(text) {
        message.textContent = text;
        message.classList.add('active');
    }

    // Скрыть сообщение
    function clearMessage() {
        message.textContent = '';
        message.classList.remove('active');
    }



    // Отправить данные на скрипт
    async function sendCreateData() {
        const url = "../actions/group/create.php";
        const data = {
            user_id: currentUserId,
            name: document.getElementById('groupName').value,
        };
        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (result.success) {
                clearMessage();
                window.location.href = 'group' + result.group_id;
                hideModal();
            } else {
                setMessage(result.message || "Ошибка соединения"); 
            }
        } catch (err) {
            console.log(err);
            setMessage("Ошибка сервера");
        }
    }



    // Начать создание
    document.getElementById('createGroup').addEventListener("click", (e) => {
        e.preventDefault();
        showModal();
    });

    // Отменить создание
    document.getElementById('cancelButton').addEventListener("click", (e) => {
        e.preventDefault();
        hideModal();
    });

    // Создание
    document.getElementById('acceptButton').addEventListener("click", (e) => {
        e.preventDefault();
        sendCreateData();
    });

    // Закрытие по клику на фон (опционально)
    document.querySelector('.modal').addEventListener('click', function(e) {
        if (e.target === this) hideModal();
    });
});
