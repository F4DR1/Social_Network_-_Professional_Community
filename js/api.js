
// Базовый URL API (localhost / production)
function getApiUrl() {
    if (location.hostname === 'localhost' || location.hostname === '127.0.0.1') {
        return '/social_network/api';  // Относительный путь для localhost
    }
    return 'https://api.sitename.com';
}

// Универсальная функция для всех AJAX запросов
async function apiRequest(endpoint, options = {}) {
    const url = `${getApiUrl()}${endpoint}`;
    
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
export async function login(data) {
    return apiRequest('/login', { body: data });
}

export async function register(data) {
    return apiRequest('/register', { body: data });
}

export async function logout() {
    return apiRequest('/logout');
}


// === СЕССИИ ===
export async function getMySessions() {
    return apiRequest('/sessions');
}

export async function terminateCurrentSession() {
    return apiRequest('/sessions/current', { method: 'DELETE' });
}

export async function terminateSession(sessionId) {
    return apiRequest(`/sessions/${sessionId}`, { method: 'DELETE' });
}

export async function terminateAllOtherSessions() {
    return apiRequest('/sessions', { method: 'DELETE' });
}


// === ПРОФИЛЬ ===
export async function getUserProfile(userId) {
    return apiRequest(`/users/${userId}`);
}

export async function updateUserProfile(userId, data) {
    return apiRequest(`/users/${userId}`, { 
        method: 'PUT', 
        body: data 
    });
}
