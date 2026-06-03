// helpers.js
document.addEventListener('DOMContentLoaded', () => {

    
    // -------------------- ДАТЫ И ВРЕМЯ --------------------
    // Форматирование даты
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        
        const day = date.getDate();
        const month = date.toLocaleDateString('ru-RU', { month: 'long' });
        const year = date.getFullYear();
        
        if (year === now.getFullYear()) {
            return `${day} ${month}`;
        } else {
            return `${day} ${month} ${year}`;
        }
    }

    // Формирование времени
    window.formatTime = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Сколько времени прошло
    window.relativeTime = function(dateString, isFullFormat = false) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;  // Разница в миллисекундах

        // Будущее время – просто показываем абсолютную дату
        if (diffMs < 0) {
            return window.formatDate(date);
        }

        const diffSeconds = Math.floor(diffMs / 1000);
        if (diffSeconds < 60) {
            return 'только что';
        }

        const diffMinutes = Math.floor(diffSeconds / 60);
        if (diffMinutes < 60) {
            return `${diffMinutes} ${isFullFormat ? 'мин.' : 'мин.'}`;
        }

        const diffHours = Math.floor(diffMinutes / 60);
        if (diffHours < 24) {
            return `${diffHours} ${isFullFormat ? 'час.' : 'ч.'}`;
        }

        const diffDays = Math.floor(diffHours / 24);
        if (diffDays < 7) {
            return `${diffDays} ${isFullFormat ? 'дн.' : 'дн.'}`;
        }

        const diffWeeks = Math.floor(diffDays / 7);
        if (diffWeeks <= 4) {
            return `${diffWeeks} ${isFullFormat ? 'нед.' : 'н.'}`;
        }

        // Более 4 недель – абсолютная дата
        return window.formatDate(date);
    }
});
