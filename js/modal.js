document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal');



    // Показать модальное окно
    window.showModal = function() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';  // Блокируем скролл
    }

    // Скрыть модальное окно
    window.hideModal = function() {
        modal.classList.remove('active');
        document.body.style.overflow = '';  // Возвращаем скролл
    }



    // Закрытие по клику на фон (опционально)
    modal?.addEventListener('click', function(e) {
        if (e.target === this) hideModal();
    });
});
