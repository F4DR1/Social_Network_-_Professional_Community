document.addEventListener('DOMContentLoaded', function() {
    const actionTriggers = document.querySelectorAll('.profile-actions .action-trigger');
    
    actionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = this.closest('.action-dropdown');
                const wrapper = this.closest('.lists-wrapper');
                
                // Закрыть все
                document.querySelectorAll('.profile-actions .action-dropdown.active').forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });
                document.querySelectorAll('.profile-actions .lists-wrapper.active').forEach(w => {
                    if (w !== wrapper) w.classList.remove('active');
                });
                
                // Переключить основной дропдаун
                dropdown?.classList.toggle('active');
                this.classList.toggle('active');
                
                // Переключить подсписок
                if (wrapper) {
                    wrapper.classList.toggle('active');
                }
            }
        });
    });
    
    // Закрыть при клике вне
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && !e.target.closest('.profile-actions .action-dropdown')) {
            document.querySelectorAll('.profile-actions .action-dropdown.active').forEach(d => {
                d.classList.remove('active');
            });
            document.querySelectorAll('.profile-actions .action-trigger.active, .profile-actions .lists-wrapper.active').forEach(el => {
                el.classList.remove('active');
            });
        }
    });
});
