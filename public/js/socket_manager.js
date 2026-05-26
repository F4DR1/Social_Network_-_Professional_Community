class SocketManager {
    constructor() {
        this.ws = null;                     // Здесь будет сам объект WebSocket
        this.messageHandlers = {};          // «Ящик» с обработчиками событий
        this.reconnectTimer = null;         // Таймер для переподключения
        this.reconnectInterval = 3000;      // Пауза между попытками (3 секунды)
    }


    // Регистрируем обработчики на разные типы сообщений
    on(eventType, callback) {
        if (!this.messageHandlers[eventType]) {
            this.messageHandlers[eventType] = [];
        }
        this.messageHandlers[eventType].push(callback);
    }


    
    // Подключиться и подписаться на чаты
    connect(token) {
        this.close();  // Закрываем старое соединение, если было
        const wsUrl = `ws://localhost:8765`;
        this.ws = new WebSocket(wsUrl);

        // Соединение установлено
        this.ws.onopen = () => {
            console.log('[WS] Connected, sending auth token...');
            this.ws.send(JSON.stringify({ token: token }));
        };

        // Пришло сообщение с сервера
        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this._dispatch(data);  // Рассылаем данные по зарегистрированным обработчикам
            } catch (e) {
                console.error('[WS] Invalid JSON:', event.data);
                console.error('[WS] Error message:', e.message);
            }
        };

        // Соединение разорвано
        this.ws.onclose = (event) => {
            console.log('[WS] Disconnected:', event.code, event.reason);
            this._scheduleReconnect(token);  // Переподключаемся
        };

        // Какая-то ошибка
        this.ws.onerror = (error) => {
            console.error('[WS] Error:', error);
            // Закрытие вызовется автоматически после ошибки
        };
    }



    // Отправить событие на сервер (например, typing_start)
    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.warn('[WS] Cannot send, socket not open');
        }
    }


    // Закрыть соединение вручную
    close() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        if (this.ws) {
            this.ws.onclose = null;  // Убираем авто-переподключение при ручном закрытии
            this.ws.close();
            this.ws = null;
        }
    }


    // Переподключение при обрыве
    _scheduleReconnect(token) {
        if (this.reconnectTimer) return;
        console.log(`[WS] Reconnecting in ${this.reconnectInterval / 1000}s...`);
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.connect(token);
        }, this.reconnectInterval);
    }


    // Вызвать все зарегистрированные обработчики для типа сообщения
    _dispatch(data) {
        const handlers = this.messageHandlers[data.type] || [];
        if (handlers) {
            handlers.forEach(fn => fn(data));
        } else {
            console.warn('[WS] No handler for message type:', data.type, data);
        }
    }
}
