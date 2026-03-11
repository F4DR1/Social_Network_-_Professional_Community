<?php
    require_once 'session_start.php';

    // Локальная PDO БД для фронтенда (простые запросы)
    class FrontendDB {
        private $pdo;
        
        public function __construct() {
            $host = 'localhost';
            $dbname = 'social_network_pc';  // БД
            $user = 'root';
            $pass = '';
            
            try {
                $this->pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user, $pass
                );
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die('Ошибка подключения к БД: ' . $e->getMessage());
            }
        }
        
        public function fetchOne($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        public function fetchAll($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    global $db_frontend, $current_user_id, $current_user;

    // Инициализация фронтенд БД
    $db_frontend = new FrontendDB();

    // API клиент
    function getApiUrl() {
        $host = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

        if ($host === 'localhost' || $host === '127.0.0.1') {
            return $protocol . '://localhost/social_network/api';
        }
        return $protocol . '://api.website.com';
    }

    function apiRequest($endpoint, $data = null) {
        $url = getApiUrl() . $endpoint;

        $cookies = [];
        if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
            $cookies[] = 'Cookie: auth_token=' . $_COOKIE['auth_token'];
        }
        
        $options = [
            'http' => [
                'header' => implode("\r\n", $cookies) . "\r\n" . "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data ?? []),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            error_log("API Request failed: " . error_get_last()['message']);
            return false;
        }

        return json_decode($result, true);
    }

    function checkAuth() {
        try {
            $response = apiRequest('/auth/check');
            
            if ($response && isset($response['success']) && $response['success'] === true) {
                return [
                    'user_id' => $response['user_id'],
                    'user' => $response['user']
                ];
            }
        } catch (Exception $e) {
            error_log('Auth check error: ' . $e->getMessage());
        }
        
        return false;
    }

    // Получение данных текущего пользователя
    $auth_check = checkAuth();
    $current_user_id = $auth_check ? $auth_check['user_id'] : null;
    $current_user = $auth_check ? $auth_check['user'] : null;
?>
