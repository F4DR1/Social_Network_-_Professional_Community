
import os
import asyncio
import aiohttp
import json
from typing import Optional, List, Dict, Set

from dotenv import load_dotenv
load_dotenv()

import redis.asyncio as redis
import websockets
from websockets.server import WebSocketServerProtocol



# ---------------------------------------------------------------------------
# Конфигурация через переменные окружения (можно задать в .env)
# ---------------------------------------------------------------------------
REDIS_HOST = os.getenv('REDIS_HOST', 'localhost')
REDIS_PORT = int(os.getenv('REDIS_PORT', '6379'))
REDIS_PASSWORD = os.getenv('REDIS_PASSWORD', None)
REDIS_CHANNEL = 'events'

WS_HOST = os.getenv('WS_HOST', '127.0.0.1')
WS_PORT = int(os.getenv('WS_PORT', '8765'))

API_KEY = os.getenv('API_KEY', '123456')

API_VALIDATE_TOKEN_URL = os.getenv(
    'API_VALIDATE_TOKEN_URL',
    'http://localhost/social_network/api/validate_token'
)
API_GET_CHAT_MEMBERS_URL = os.getenv(
    'API_GET_CHAT_MEMBERS_URL',
    'http://localhost/social_network/api/chats/members/get'
)





# ---------------------------------------------------------------------------
# Хранилище подключённых клиентов и их подписок на чаты
# ---------------------------------------------------------------------------
# Список подключений активных пользователей
# {user_id: WebSocketServerProtocol}
connected_users: Dict[int, WebSocketServerProtocol] = {}





# Запросы к API
async def validate_token(token: str) -> Optional[int]:
    """
    Отправляет токен в PHP API и возвращает user_id.
    Возвращает None, если произошла ошибка или токен невалидный.
    """
    try:
        async with aiohttp.ClientSession() as session:
            # API принимает POST-запрос с JSON {'token': '...'}
            # и возвращает {'user_id': 123} или ошибку.
            headers = {'X-Api-Key': API_KEY}
            async with session.post(
                API_VALIDATE_TOKEN_URL,
                headers=headers,
                json={'token': token},
                timeout=aiohttp.ClientTimeout(total=5)  # Таймаут 5 секунд
            ) as resp:
                if resp.status == 200:
                    data = await resp.json()
                    return data.get('user_id')  # Ожидаем целое число
                else:
                    print(f'[API] API error: {resp.status}')
                    return None
    except Exception as e:
        print(f'[HTTP] HTTP request failed: {e}')
        return None

async def get_chat_members_ids(chat_id: int) -> Optional[List[int]]:
    """
    Отправляет chat_id в PHP API и возвращает chat_members_ids.
    Возвращает None, если произошла ошибка или данные не получены.
    """
    try:
        async with aiohttp.ClientSession() as session:
            # Формируем полный URL: базовый + chat_id + '/ids'
            base = API_GET_CHAT_MEMBERS_URL.rstrip('/')
            url = f'{base}/{chat_id}/ids'

            # API принимает POST-запрос с id {'chatId': '...'}
            # и возвращает {'chatMembersIds': [123, 456]} или ошибку.
            headers = {'X-Api-Key': API_KEY}
            async with session.get(
                url,
                headers=headers,
                timeout=aiohttp.ClientTimeout(total=5)  # Таймаут 5 секунд
            ) as resp:
                if resp.status == 200:
                    data = await resp.json()
                    members = data.get('chatMembersIds')
                    if isinstance(members, list):
                        return [int(uid) for uid in members]
                    else:
                        print('[API] Unexpected data format:', data)
                        return None
                else:
                    print(f'[API] API error: {resp.status}')
                    return None
    except Exception as e:
        print(f'[HTTP] HTTP request failed: {e}')
        return None




# ---------------------------------------------------------------------------
# Функции для работы с подписками
# ---------------------------------------------------------------------------
async def send_to_user(user_id: int, message: str):
    """Отправить сообщение конкретному пользователю, если он онлайн."""
    ws = connected_users.get(user_id)
    if ws:
        try:
            await ws.send(message)
        except websockets.exceptions.ConnectionClosed:
            pass

async def broadcast_to_chat(chat_id: int, message: str, user_id: int):
    """Отправить сообщение всем онлайн-пользователям в чате, кроме указанного."""
    chat_members_ids = await get_chat_members_ids(chat_id)
    if not chat_members_ids:
        print(f'[WS] No members found or error fetching members for chat {chat_id}')
        return
    for uid in chat_members_ids:
        if uid != user_id:
            await send_to_user(uid, message)





# ---------------------------------------------------------------------------
# Обработчик WebSocket-соединения
# ---------------------------------------------------------------------------
async def handler(websocket: WebSocketServerProtocol):
    """Главный обработчик нового клиента."""
    # Ожидаем первое сообщение с токеном (таймаут 5 секунд)
    try:
        raw = await asyncio.wait_for(websocket.recv(), timeout=5.0)
    except asyncio.TimeoutError:
        await websocket.close(1008, 'Authentication timeout')
        return
    except websockets.exceptions.ConnectionClosed:
        return  # Клиент отключился, не отправив данные

    # Парсим JSON и извлекаем токен
    try:
        msg = json.loads(raw)
        token = msg.get('token')
    except json.JSONDecodeError:
        await websocket.close(1008, 'Invalid JSON')
        return

    if not token:
        await websocket.close(1008, 'Token required')
        return
        
    # # [[[LEGACY]]]
    # # Аутентификация по токену в URL (ws://host:port?token=...)
    # token = websocket.request.query_params.get('token')
    # if not token:
    #     await websocket.close(1008, 'Token required')
    #     return


    # Валидируем токен с получением id пользователя
    user_id = await validate_token(token)
    if not user_id:
        await websocket.close(1008, 'Invalid token')
        return

    # Сохраняем подключение
    connected_users[user_id] = websocket
    print(f'[+] User {user_id} connected')

    try:
        # Основной цикл приёма сообщений от клиента
        async for raw_message in websocket:
            try:
                msg = json.loads(raw_message)
                await handle_client_message(user_id, msg)

            except json.JSONDecodeError:
                await websocket.send(json.dumps({'error': 'Invalid JSON'}))

    except websockets.exceptions.ConnectionClosed:
        print(f'[-] User {user_id} disconnected')
    finally:
        connected_users.pop(user_id, None)
        print(f'[-] User {user_id} cleaned up')


async def handle_client_message(sender_id: int, data: dict):
    """Маршрутизация событий от клиента."""
    msg_type = data.get('type')
    if not msg_type:
        return


    if msg_type in ('typing_start', 'typing_end'):
        # Рассылаем событие всем в чате, кроме отправителя
        chat_id = data.get('chatId')
        
        payload = json.dumps({
            'type': msg_type,
            'chatId': chat_id,
            'userId': sender_id
        })
        await broadcast_to_chat(chat_id, payload, sender_id)

    # elif msg_type == "mark_read":
    #     # Можно отправить событие собеседникам, что сообщения прочитаны
    #     payload = json.dumps({
    #         "type": "messages_read",
    #         "chatId": chat_id,
    #         "userId": sender_id,
    #         "lastMessageId": data.get("last_message_id")
    #     })
    #     await broadcast_to_chat(chat_id, payload, sender_id)



# ---------------------------------------------------------------------------
# Redis listener – получение событий от PHP
# ---------------------------------------------------------------------------
async def redis_listener():
    """Подписываемся на Redis-канал и пересылаем события нужным WebSocket-клиентам."""
    try:
        r = redis.Redis(
            host=REDIS_HOST,
            port=REDIS_PORT,
            password=REDIS_PASSWORD,
            decode_responses=True,
            protocol=2,
        )
        pubsub = r.pubsub()
        await pubsub.subscribe(REDIS_CHANNEL)
        print(f'[Redis] Subscribed to channel "{REDIS_CHANNEL}"')

        async for message in pubsub.listen():
            # print(f'[Redis][DEBUG] RAW MESSAGE: {message}')
            if message['type'] == 'message':
                try:
                    data = json.loads(message['data'])
                    # print(f'[Redis][DEBUG] Received: {data}')
                    await dispatch_redis_event(data)
                except json.JSONDecodeError:
                    print('[Redis] Invalid JSON')
    
    except Exception as e:
        print(f'[Redis] Error: {e}')


async def dispatch_redis_event(data: dict):
    """Обрабатывает событие, пришедшее из Redis."""
    event_type = data.get('type')

    if event_type == 'new_message':
        # Сообщаем о новом сообщении
        chat_id = data.get('chatId')
        user_id = data.get('userId')
        message_id = data.get('messageId')

        payload = json.dumps({
            'type': 'new_message_signal',
            'chatId': chat_id,
            'userId': user_id,
            'messageId': message_id
        })
        await broadcast_to_chat(chat_id, payload, user_id)

    elif event_type == 'new_notification':
        # Сообщаем о новом уведомлении
        user_id = data.get('userId')
        notification_id = data.get('notificationId')

        payload = json.dumps({
            'type': 'new_notification_signal',
            'notificationId': notification_id
        })
        await send_to_user(user_id, payload)





# ---------------------------------------------------------------------------
# Точка входа и корректное завершение
# ---------------------------------------------------------------------------
async def main():
    # Запускаем фоновую задачу для Redis
    redis_task = asyncio.create_task(redis_listener())

    # Настраиваем WebSocket-сервер
    stop = asyncio.Future()

    async with websockets.serve(handler, WS_HOST, WS_PORT):
        print(f'[WS] Server listening on ws://{WS_HOST}:{WS_PORT}')
        # Ждём сигнал завершения (Ctrl+C) или нештатной остановки
        try:
            await stop
        except asyncio.CancelledError:
            pass
        finally:
            redis_task.cancel()
            try:
                await redis_task
            except asyncio.CancelledError:
                pass

if __name__ == "__main__":
    # Корректная обработка Ctrl+C
    asyncio.run(main())