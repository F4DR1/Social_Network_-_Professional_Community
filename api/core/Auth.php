<?php
    class Auth {
        private $db;
        private $user = null;
        private $session = null;
        
        public function __construct($db) {
            $this->db = $db;
        }
        
        /**
         * Проверяет авторизацию по токену
         */
        public function check() {
            $token = Helpers::extractToken();
            
            if ($token) {
                // Ищем сессию с таким токеном
                $session = $this->db->fetchOne(
                    "SELECT * FROM sessions 
                    WHERE token = ?",
                    [$token]
                );

                if ($session) {
                    // Получаем данные пользователя отдельно
                    $user = $this->db->fetchOne(
                        "SELECT id, phone, email, firstname, lastname, created_at 
                        FROM users WHERE id = ?",
                        [$session['user_id']]
                    );
                    
                    if ($user) {
                        // Обновляем время последней активности
                        $this->db->query(
                            "UPDATE sessions SET last_activity = NOW() WHERE id = ?",
                            [$session['id']]
                        );
                        
                        $this->user = $user;
                        $this->session = $session;
                        return true;
                    }
                }
            }
            
            Helpers::errorResponse('Не авторизован', 401);
        }
    
        public function getCurrentSession() {
            return $this->session;
        }
    
        public function getAllUserSessions() {
            if (!$this->user) return [];
            
            // Получаем все сессии текущего пользователя
            return $this->db->fetchAll(
                "SELECT id, device_name, device_type, ip_address, last_activity, created_at 
                FROM sessions 
                WHERE user_id = ? 
                ORDER BY last_activity DESC",
                [$this->user['id']]
            );
        }
        
        // Получить текущего пользователя
        public function user() {
            return $this->user;
        }
    }
?>