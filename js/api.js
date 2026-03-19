// Универсальная функция для всех AJAX запросов
async function apiRequest(endpoint, options = {}) {
    const url = `${window.APP_CONFIG.API}${endpoint}`;
    
    const config = {
        method: options.method || 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        },
        credentials: 'include',  // Cookies для сессий
        ...options
    };

    if (options.body && typeof options.body === 'object') {
        config.body = JSON.stringify(options.body);
    }

    try {
        const response = await fetch(url, config);
        return await response.json();
    } catch (error) {
        // throw new Error('Ошибка соединения с API');
        throw new Error(error);
    }
}



// === АВТОРИЗАЦИЯ ===
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


// === СЕССИИ ===
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


// === ОТНОШЕНИЯ ===
export async function relationshipsList() {
    return apiRequest(`/relationships/list`, {
        method: 'GET'
    });
}
export async function relationshipsGet(userId, relatedUserId) {
    return apiRequest(`/relationships/get/${userId}/${relatedUserId}`, {
        method: 'GET',
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


// === ПРОФИЛЬ ===
// export async function getUserProfile(userId) {
//     return apiRequest(`/users/${userId}`);
// }

// export async function updateUserProfile(userId, data) {
//     return apiRequest(`/users/${userId}`, { 
//         method: 'PUT', 
//         body: data 
//     });
// }


// === ГРУППЫ ===
export async function groupsListGet(userId, isAdmin) {
    return apiRequest(`/groups/list/${userId}/${isAdmin}`, {
        method: 'GET'
    });
}
export async function groupsCreate(data) {
    return apiRequest(`/groups/create`, {
        method: 'POST',
        body: data
    });
}
export async function groupsEdit(userId, data) {
    return apiRequest(`/groups/edit/${userId}`, {
        method: 'POST',
        body: data
    });
}
