// modal.js
document.addEventListener('DOMContentLoaded', function() {
    let modalStack = [];  // Стек ID открытых окон
    

    // Создать HTML модального окна
    window.createModalHTML = function(modalId, contentHTML) {
        // Удаляем предыдущее окно с таким же ID, чтобы избежать дублей
        const existingModal = document.getElementById(modalId);
        if (existingModal) existingModal.remove();
        
        const modalHTML = `
            <div class="modal" id="${modalId}">
                <div class="modal-content">
                    ${contentHTML}
                </div>
            </div>
        `
        document.querySelector('main').insertAdjacentHTML("beforeend", modalHTML);
        
        // Закрытие по клику на фон
        document.getElementById(modalId).addEventListener('click', function(e) {
            if (e.target === this)
                hideModal(modalId);
        });
    }



    // Показать модальное окно
    window.showModal = function(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        // Если окно уже в стеке – игнорируем повторный вызов
        if (modalStack.includes(id)) return;

        // Скрываем текущее активное окно (верхнее в стеке)
        if (modalStack.length > 0) {
            const topId = modalStack[modalStack.length - 1];
            const topModal = document.getElementById(topId);
            if (topModal) topModal.classList.remove('active');
        } else {
            // Первое окно – блокируем скролл
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
        }

        // Показываем новое окно и добавляем в стек
        modal.classList.add('active');
        modalStack.push(id);
    };

    // Скрыть модальное окно (если ID не указан – скрываем верхнее)
    window.hideModal = function(id = null, remove = false) {
        let targetId = id;
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
            }, 300);  // задержка для анимации закрытия
        }
    };




    
    window.confirmationModal = function(text, title = null, btnCount = 2, cancelBtnText = null, acceptBtnText = null) {
        title = title ?? 'Подтверждение действия';
        const modalId = 'confirmationModal';
        const cancelBtnId = 'confirmationCancelButton';
        const acceptBtnId = 'confirmationAcceptButton';

        const cancelBtnHTML = `<button class="modal-btn" id="${cancelBtnId}">${cancelBtnText || 'Отмена'}</button>`
        const acceptBtnHTML = `<button class="modal-btn" id="${acceptBtnId}">${acceptBtnText || 'Подтвердить'}</button>`
        const confirmationHTML = `
            <div class="modal-title">
                <h2>${title}</h2>
            </div>
            <div class="modal-main confirmation">
                <p>${text}</p>
            </div>
            <div class="modal-footer">
                ${btnCount < 1 ? '' : cancelBtnHTML}
                ${btnCount < 2 ? '' : acceptBtnHTML}
            </div>
        `
        createModalHTML(modalId, confirmationHTML);
        showModal(modalId);



        // Возвращаем Promise, который разрешится при выборе пользователя
        return new Promise((resolve) => {
            const acceptBtn = document.getElementById(acceptBtnId);
            const cancelBtn = document.getElementById(cancelBtnId);
            const modal = document.getElementById(modalId);

            const cleanupAndRemove = (result) => {
                acceptBtn?.removeEventListener('click', onAccept);
                cancelBtn?.removeEventListener('click', onCancel);
                modal?.removeEventListener('click', onBackdropClick);
                
                // Удаляем окно
                hideModal(modalId);
                
                // Возвращаем результат
                resolve(result);
            };

            const onAccept = () => cleanupAndRemove(true);
            const onCancel = () => cleanupAndRemove(false);
            const onBackdropClick = (e) => {
                if (e.target === modal) cleanupAndRemove(false);
            };

            acceptBtn?.addEventListener('click', onAccept);
            cancelBtn?.addEventListener('click', onCancel);
            modal?.addEventListener('click', onBackdropClick);
        });
    }
});
