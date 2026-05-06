// Универсальная функция для всех AJAX запросов
async function apiRequest(endpoint, options = {}) {
    const url = `${window.APP_CONFIG.API}${endpoint}`;
    const isFormData = options.body instanceof FormData;
    
    const config = {
        method: options.method || 'POST',
        headers: {
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...options.headers
        },
        credentials: 'include',  // Cookies для сессий
        ...options
    };

    if (options.body) {
        config.body = isFormData ? options.body : JSON.stringify(options.body);
    }

    try {
        const response = await fetch(url, config);
        return await response.json();
    } catch (error) {
        // throw new Error('Ошибка соединения с API');
        throw new Error(error);
    }
}



// ========== АВТОРИЗАЦИЯ ==========
export async function authLogout() {
    return apiRequest('/logout', {
        method: 'POST'
    });
}
export async function authLogin(data) {
    return apiRequest('/login', {
        method: 'POST',
        body: data
    });
}
export async function authRegister(data) {
    return apiRequest('/register', {
        method: 'POST',
        body: data
    });
}
export async function authCheck() {
    return apiRequest('/auth/check', {
        method: 'POST'
    });
}


// ========== СЕССИИ ==========
export async function sessionsGetMy() {
    return apiRequest('/sessions', {
        method: 'GET'
    });
}
export async function sessionsTerminateCurrent() {
    return apiRequest('/sessions/current', {
        method: 'DELETE'
    });
}
export async function sessionsTerminate(data) {
    return apiRequest(`/sessions/id`, {
        method: 'DELETE',
        body: data
    });
}
export async function sessionsTerminateAll() {
    return apiRequest('/sessions', {
        method: 'DELETE'
    });
}


// ========== ОТНОШЕНИЯ ==========
export async function relationshipsList() {
    return apiRequest(`/relationships/list`, {
        method: 'GET'
    });
}
export async function relationshipsUsersGet(userId) {
    return apiRequest(`/relationships/get/users/${userId}`, {
        method: 'GET'
    });
}
export async function relationshipsSubscribe(data) {
    return apiRequest(`/relationships/subscribe`, {
        method: 'PUT',
        body: data
    });
}
export async function relationshipsUnsubscribe(data) {
    return apiRequest(`/relationships/unsubscribe`, {
        method: 'DELETE',
        body: data
    });
}
export async function relationshipsBlock(data) {
    return apiRequest(`/relationships/block`, {
        method: 'PUT',
        body: data
    });
}
export async function relationshipsChangeList(data) {
    return apiRequest(`/relationships/change-list`, {
        method: 'PUT',
        body: data
    });
}


// ========== ПРОФИЛЬ ==========
// export async function getUserProfile(userId) {
//     return apiRequest(`/users/${userId}`);
// }
// export async function updateUserProfile(userId, data) {
//     return apiRequest(`/users/${userId}`, { 
//         method: 'PUT', 
//         body: data 
//     });
// }


// ========== НАВЫКИ ПОЛЬЗОВАТЕЛЯ ==========
export async function skillLevelsGet() {
    return apiRequest(`/skills/levels/get`, {
        method: 'GET'
    });
}
export async function skillsGet(data) {
    return apiRequest(`/skills/get`, {
        method: 'POST',
        body: data
    });
}
export async function userSkillsGet(userId, data) {
    return apiRequest(`/users/user-skills/get/${userId}`, {
        method: 'POST',
        body: data
    });
}
export async function userSkillsAdd(data) {
    return apiRequest(`/users/user-skills/add`, {
        method: 'POST',
        body: data
    });
}
export async function userSkillsEdit(data) {
    return apiRequest(`/users/user-skills/edit`, {
        method: 'PUT',
        body: data
    });
}
export async function userSkillsDelete(data) {
    return apiRequest(`/users/user-skills/delete`, {
        method: 'DELETE',
        body: data
    });
}
export async function userSkillEndorsementAdd(data) {
    return apiRequest(`/users/user-skills/endorsement/add`, {
        method: 'POST',
        body: data
    });
}
export async function userSkillEndorsementDelete(data) {
    return apiRequest(`/users/user-skills/endorsement/delete`, {
        method: 'DELETE',
        body: data
    });
}


// ========== ГРУППЫ ==========
export async function groupsListGet(userId) {
    return apiRequest(`/groups/list/${userId}`, {
        method: 'GET'
    });
}
export async function groupsCreate(data) {
    return apiRequest(`/groups/create`, {
        method: 'POST',
        body: data
    });
}
export async function groupsEdit(data) {
    return apiRequest(`/groups/edit`, {
        method: 'POST',
        body: data
    });
}
export async function groupsMembers(groupId) {
    return apiRequest(`/groups/members/${groupId}`, {
        method: 'GET'
    });
}
export async function groupsSubscribe(data) {
    return apiRequest(`/groups/subscribe`, {
        method: 'POST',
        body: data
    });
}
export async function groupsUnsubscribe(data) {
    return apiRequest(`/groups/unsubscribe`, {
        method: 'POST',
        body: data
    });
}


// ========== ПОСТЫ ==========
export async function postsGetByFeed() {
    return apiRequest(`/posts/feed`, {
        method: 'GET'
    });
}
export async function postsGetAllByUser(userId) {
    return apiRequest(`/posts/user/${userId}`, {
        method: 'GET'
    });
}
export async function postsGetAllByGroup(groupId) {
    return apiRequest(`/posts/group/${groupId}`, {
        method: 'GET'
    });
}
export async function postGet(postId) {
    return apiRequest(`/post/get/${postId}`, {
        method: 'GET'
    });
}
export async function postPublicate(data) {
    return apiRequest(`/post/publicate`, {
        method: 'POST',
        body: data
    });
}
export async function postDelete(data) {
    return apiRequest(`/post/delete`, {
        method: 'POST',
        body: data
    });
}


// ========== КОНТЕНТ ==========
export async function contentArticleGet(articleId) {
    return apiRequest(`/content/article/get/${articleId}`, {
        method: 'GET'
    });
}
export async function contentArticleCreate(data) {
    return apiRequest(`/content/article/create`, {
        method: 'POST',
        body: data
    });
}
export async function fileUpload(formData) {
    return apiRequest(`/file/upload`, {
        method: 'POST',
        body: formData
    });
}
