<?php
    require_once __DIR__ . '/../bootstrap.php';
    require_once CONFIG_PATH . '/config.php';
    require_once INCLUDES_PATH . '/session_start.php';
    global $current_user_id, $current_user;


    /**
     * Универсальная функция для запросов к API (укороченная)
     */
    function apiRequest($endpoint, $options = []) {
        try {
            $url = API . $endpoint;
            $method = strtoupper($options['method'] ?? 'POST');
            $timeout = $options['timeout'] ?? 30;
            $sendCookies = $options['send_cookies'] ?? true;

            // Заголовки
            $headers = array_merge(['Content-Type: application/json'], $options['headers'] ?? []);

            // Тело запроса
            $body = null;
            if (isset($options['body']) && is_array($options['body'])) {
                $body = json_encode($options['body'], JSON_UNESCAPED_UNICODE);
                if ($body === false) throw new Exception('JSON error');
            }

            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
            ];

            // Тело только для не-GET методов
            if ($method !== 'GET' && $body !== null) {
                $curlOptions[CURLOPT_POSTFIELDS] = $body;
            }

            // Куки в правильном формате
            if ($sendCookies && !empty($_COOKIE)) {
                $cookieString = '';
                foreach ($_COOKIE as $name => $value) {
                    $cookieString .= $name . '=' . $value . '; ';
                }
                $curlOptions[CURLOPT_COOKIE] = rtrim($cookieString, '; ');
            }

            curl_setopt_array($ch, $curlOptions);


            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) throw new Exception("cURL: $error");

            // Диагностика JSON
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON error: " . json_last_error_msg());
            }


            // Возвращаем HTTP код и данные
            return [
                'success' => $httpCode < 400,
                'http_code' => $httpCode,
                'data' => $decoded ?? $response,
                'raw' => $response
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'http_code' => $httpCode
            ];
        }
    }




    
    // === АВТОРИЗАЦИЯ ===
    function authCheck() {
        return apiRequest('/auth/check', [
            'method' => 'POST'
        ]);
    }

    
    // === ОТНОШЕНИЯ ===
    function relationshipsList() {
        return apiRequest("/relationships/list", [
            'method' => 'GET'
        ]);
    }

    
    // === ПОЛЬЗОВАТЕЛИ ===
    function usersGetId($userId) {
        return apiRequest("/users/{$userId}", [
            'method' => 'GET'
        ]);
    }
    function usersGetLinkname($userLinkname) {
        return apiRequest("/users/by-link/{$userLinkname}", [
            'method' => 'GET'
        ]);
    }


    // === ГРУППЫ ===
    function groupsGetId($groupId) {
        return apiRequest("/groups/{$groupId}", [
            'method' => 'GET'
        ]);
    }
    function groupsGetLinkname($groupLinkname) {
        return apiRequest("/groups/by-link/{$groupLinkname}", [
            'method' => 'GET'
        ]);
    }
    function groupsUserIsAdmin($groupId, $userId) {
        return apiRequest("/groups/is-admin/$groupId/$userId", [
            'method' => 'GET'
        ]);
    }



    // Получение данных текущего пользователя
    $auth_check = authCheck();
    $current_user_id = $auth_check['success'] ? $auth_check['data']['user_id'] : null;
    $current_user = $auth_check['success'] ? $auth_check['data']['user'] : null;
?>
