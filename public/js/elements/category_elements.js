// category_elements.js
document.addEventListener('DOMContentLoaded', function() {

    // Функция создания HTML карточки пользователя
    window.createUserHTML = function(user) {
        const userId = user.id;
        const userLinkname = user.linkname ?? `user${userId}`;
        const userFullname = user.fullname ?? `${user.firstname} ${user.lastname}`;
        const userPhoto = user.photo;
        
        return `
            <div class="card">
                <a class="photo">
                    <img src="${userPhoto}" alt="${userFullname}" width=80>
                </a>
                <div class="main">
                    <a href="${userLinkname}" class="name-line">${userFullname}</a>
                    <a href="msg?type=user&id=${userId}" class="message-line">Написать сообщение</a>
                </div>
            </div>
        `
    }
    
    // Функция создания HTML карточки группы
    window.createGroupHTML = function(group) {
        const groupId = group.id;
        const groupName = group.name;
        const groupLinkname = group.linkname ?? `group${groupId}`;
        const groupPhoto = group.photo;
        
        return `
            <div class="card">
                <a class="photo">
                    <img src="${groupPhoto}" alt="${groupName}" width=80>
                </a>
                <div class="main">
                    <a href="${groupLinkname}" class="name-line">${groupName}</a>
                    <!-- <a href="msg?type=group&id=${groupId}" class="message-line">Написать в чат группы</a> -->
                </div>
            </div>
        `
    }
});
