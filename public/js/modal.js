// modal.js
document.addEventListener('DOMContentLoaded', function() {
    let modalStack = [];  // Стек ID открытых окон


    window.isNoModals = function() {
        return (modalStack.length === 0);
    };
    


    // Создать HTML модального окна
    window.createModalHTML = function(modalId, contentHTML, isDisableHideActions = false) {
        // Удаляем предыдущее окно с таким же ID, чтобы избежать дублей
        const existingModal = document.getElementById(modalId);
        // if (existingModal) existingModal.remove();
        if (existingModal) {
            console.warn(`Модальное окно с id "${modalId}" уже существует`);
            return null;
        }
        
        // Кнопка закрытия окна
        const closeBtn = `
            <button class="modal-close-btn">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                    <line x1="9" y1="15" x2="15" y2="9"/>
                </svg>
            </button>
        `;
        
        // Модальное окно ("modal") должно быть на весь экран, а уже контент ("modal-content") - это панель
        const modalHTML = `
            <div class="modal" id="${modalId}">
                <div class="modal-content">
                    ${contentHTML}
                </div>
                ${isDisableHideActions ? '' : closeBtn}
            </div>
        `;
        document.querySelector('main').insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById(modalId);

        // Закрытие окна стандартными методами
        if (!isDisableHideActions) {
            // Закрытие по крестику
            modal.querySelector('.modal-close-btn').addEventListener('click', (e) => {
                e.preventDefault();
                hideModal(modalId, true);
            });
            
            // Закрытие по клику на фон
            modal.addEventListener('click', (e) => {
                if (e.target === modal) hideModal(modalId);
            });
        }

        return modal;
    };



    // Показать модальное окно
    window.showModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Если окно уже в стеке – игнорируем повторный вызов
        if (modalStack.includes(modalId)) return;

        // Скрываем текущее активное окно (верхнее в стеке)
        if (modalStack.length > 0) {
            const topId = modalStack[modalStack.length - 1];
            const topModal = document.getElementById(topId);
            if (topModal) topModal.classList.remove('active');
            
        } else {
            // // Первое окно – блокируем скролл
            // document.body.style.overflow = 'hidden';
            // document.body.classList.add('modal-open');
        }

        // Показываем новое окно и добавляем в стек
        modal.classList.add('active');
        modalStack.push(modalId);
    };

    // Скрыть модальное окно (если ID не указан – скрываем верхнее)
    window.hideModal = function(modalId = null, remove = false) {
        let targetId = modalId;
        if (!targetId) {
            if (modalStack.length === 0) return;
            targetId = modalStack[modalStack.length - 1];
        }

        // Закрывать можно только верхнее окно
        if (modalStack.length === 0 || modalStack[modalStack.length - 1] !== targetId) {
            console.warn(`Модальное окно ${targetId} не находится на вершине стека`);
            return;
        }

        const modal = document.getElementById(targetId);
        if (modal) modal.classList.remove('active');

        // Удаляем из стека
        modalStack.pop();

        // Показываем предыдущее окно, если есть
        if (modalStack.length > 0) {
            const prevId = modalStack[modalStack.length - 1];
            const prevModal = document.getElementById(prevId);
            if (prevModal) prevModal.classList.add('active');

        } else {
            // Стек пуст – разблокируем скролл
            document.body.style.overflow = '';
            document.body.classList.remove('modal-open');
        }

        // Удаление, если запрошено
        if (remove && modal) {
            setTimeout(() => {
                if (modal.parentNode) modal.remove();
            }, 200);  // Задержка для анимации закрытия
        }
    };




    
    // Модальное окно с информацией
    window.informationModal = function(text, title = null, cancelBtnText = null) {
        title = title ?? 'Информация';
        const modalId = 'informationModal';
        formatedModal(modalId, text, title, 1, cancelBtnText);
    };
    
    // Модальное окно подтверждения действия
    window.confirmationModal = function(text, title = null, cancelBtnText = null, acceptBtnText = null) {
        title = title ?? 'Подтверждение действия';
        const modalId = 'confirmationModal';
        formatedModal(modalId, text, title, 2, cancelBtnText, acceptBtnText);
    };



    // Создаёт форматированное модальное окно
    function formatedModal(modalId, text, title, btnCount, cancelBtnText = null, acceptBtnText = null) {
        const formatedModalHTML = `
            <div class="modal-title">
                <h2>${title}</h2>
            </div>
            <div class="modal-main confirmation">
                <p>${text}</p>
            </div>
            <div class="modal-footer">
                ${btnCount < 1 ? '' : `<button class="modal-btn confiramtion-modal-cancel-btn">${cancelBtnText || 'Отмена'}</button>`}
                ${btnCount < 2 ? '' : `<button class="modal-btn confiramtion-modal-accept-btn">${acceptBtnText || 'Подтвердить'}</button>`}
            </div>
        `;
        const modal = createModalHTML(modalId, formatedModalHTML);
        showModal(modalId);



        // Возвращаем Promise, который разрешится при выборе пользователя
        return new Promise((resolve) => {
            const acceptBtn = modal.querySelector('.confiramtion-modal-accept-btn');
            const cancelBtn = modal.querySelector('.confiramtion-modal-cancel-btn');

            // Очищаем и удаляем модальное окно
            const cleanupAndRemove = (result) => {
                // Отключаем ивенты для кнопок
                acceptBtn.removeEventListener('click', onAccept);
                cancelBtn.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onBackdropClick);
                
                // Удаляем окно
                hideModal(modalId, true);
                
                // Возвращаем результат
                resolve(result);
            };

            const onAccept = () => cleanupAndRemove(true);
            const onCancel = () => cleanupAndRemove(false);
            const onBackdropClick = (e) => {
                if (e.target === modal) cleanupAndRemove(false);
            };

            // Инициализация кликов на элементы
            acceptBtn.addEventListener('click', onAccept);
            cancelBtn.addEventListener('click', onCancel);
            modal.addEventListener('click', onBackdropClick);
        });
    };
});
