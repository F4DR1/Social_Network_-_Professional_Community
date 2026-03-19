import { authLogout } from './api.js';

document.addEventListener("DOMContentLoaded", function() {
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




    
    // Вступление в группу
    document.getElementById("logoutButton").addEventListener("click", async (e) => {
        e.preventDefault();

        try {
            const result = await authLogout();

            if (result.success) {
                window.location.href = window.APP_CONFIG.BASE_URL;
            } else {
                console.log(result.error || "Ошибка выхода из системы");
            }

        } catch (err) {
            console.log(err.error);
        }
    });
});
