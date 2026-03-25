document.addEventListener('DOMContentLoaded', function() {

    // Функция создания HTML карточки пользователя
    window.createUserHTML = function(user) {
        const fullname = `${user.firstname} ${user.lastname}`;
        const linkname = user.linkname ?? `user${user.id}`;
        const photo = user.photo ?? `${window.APP_CONFIG.IMAGES}/empty.webp`;
        
        return `
            <div class="group-panel">
                <img src="${photo}" alt="${fullname}" width=80>
                <a href="${linkname}" class="name-line">${fullname}</a>
                <a href="messages?type=user&id=${user.id}" class="message-line">Написать сообщение</a>
            </div>
        `
    }
    
    // Функция создания HTML карточки группы
    window.createGroupHTML = function(group) {
        const name = group.name;
        const linkname = group.linkname ?? `group${group.id}`;
        const photo = group.photo ?? `${window.APP_CONFIG.IMAGES}/empty.webp`;
        
        return `
            <div class="group-panel">
                <img src="${photo}" alt="${name}" width=80>
                <a href="${linkname}" class="name-line">${name}</a>
                <a href="messages?type=group&id=${group.id}" class="message-line">Написать в чат группы</a>
            </div>
        `
    }
});
