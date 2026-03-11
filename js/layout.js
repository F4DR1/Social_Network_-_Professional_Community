 document.addEventListener('DOMContentLoaded', () => {
    const profileDropdown = document.querySelector('.profile-dropdown');
    const profileTrigger = document.querySelector('.profile-trigger');
    
    if (profileTrigger) {
        profileTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.toggleAttribute('open');
        });
        
        // Закрытие при клике вне
        document.addEventListener('click', () => {
            profileDropdown.removeAttribute('open');
        });
    }
});
