<?php
    require_once 'core/Helpers.php';
    
    class SessionController {
        private $db;
        private $auth;
        
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        
        /**
         * GET /sessions - получить все мои сессии
         */
        public function getAllMySessions() {
            $this->auth->check();
            $sessions = $this->auth->getAllUserSessions();
            
            // Добавляем флаг "текущая сессия"
            $currentSessionId = $this->auth->getCurrentSession()['id'];
            foreach ($sessions as &$session) {
                $session['is_current'] = ($session['id'] == $currentSessionId);
                // Форматируем даты для удобства
                $session['last_activity_human'] = $this->timeAgo($session['last_activity']);
            }
            
            echo json_encode(['sessions' => $sessions]);
        }
        
        /**
         * DELETE /sessions/current - завершить текущую сессию
         */
        public function terminateCurrentSession() {
            $this->auth->check();
            $currentSession = $this->auth->getCurrentSession();
            
            if ($currentSession) {
                $this->db->query(
                    "DELETE FROM sessions WHERE id = ?",
                    [$currentSession['id']]
                );
                
                if (Helpers::isWebRequest()) {
                    Helpers::deleteAuthCookie();
                }
            }
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * DELETE /sessions/{id} - завершить конкретную сессию
         */
        public function terminateSession($sessionId) {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            // Удаляем только сессии текущего пользователя
            $this->db->query(
                "DELETE FROM sessions WHERE id = ? AND user_id = ?",
                [$sessionId, $currentUser['id']]
            );
            
            echo json_encode(['success' => true]);
        }

        /**
         * DELETE /sessions - завершить ВСЕ сессии, кроме текущей
         */
        public function terminateAllOtherSessions() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            $currentSession = $this->auth->getCurrentSession();
            
            $this->db->query(
                "DELETE FROM sessions WHERE user_id = ? AND id != ?",
                [$currentUser['id'], $currentSession['id']]
            );
            
            echo json_encode(['success' => true]);
        }
        

        
        private function timeAgo($timestamp) {
            $time = strtotime($timestamp);
            $diff = time() - $time;
            
            if ($diff < 60) return 'только что';
            if ($diff < 3600) return round($diff/60) . ' минут назад';
            if ($diff < 86400) return round($diff/3600) . ' часов назад';
            return date('d.m.Y H:i', $time);
        }
    }
?>
