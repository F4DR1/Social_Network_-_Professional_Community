<?php
    require_once 'core/Helpers.php';
    
    class SessionController {
        private $db;
        private $auth;
        
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        

        
        // Формируем текст последней активности
        private static function timeAgo($timestamp) {
            $time = strtotime($timestamp);
            $diff = time() - $time;
            
            if ($diff < 60) return 'только что';
            if ($diff < 3600) return round($diff/60) . ' минут назад';
            if ($diff < 86400) return round($diff/3600) . ' часов назад';
            return date('d.m.Y H:i', $time);
        }
        




        /**
         * GET /validate_token - верифицировать токен и получить id пользователя
         */
        public function validateToken() {
            // Отклоняем несанкционированные запросы проверки токена пользователя
            if (!Helpers::isInternalRequest()) Helpers::errorResponse('No access to token verification', 403);

            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'] ?? '';
            
            $session = $this->db->fetchOne("
                    SELECT
                        user_id
                    FROM
                        sessions
                    WHERE
                        token = ?
                ",
                [$token]
            );

            if (empty($session)) Helpers::errorResponse('Session not found', 404);

            $user_id = (int) $session['user_id'];
            
            Helpers::jsonResponse(['success' => true, 'user_id' => $user_id]);
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
                $session['last_activity_human'] = self::timeAgo($session['last_activity']);
                $session['device_type_photo'] = Helpers::fileUrl(Helpers::imagePlaceholder($session['device_type']));
            }
            
            Helpers::jsonResponse(['success' => true, 'sessions' => $sessions]);
        }
        
        /**
         * DELETE /sessions/current - завершить текущую сессию
         */
        public function terminateCurrentSession() {
            $this->auth->check();
            $currentSession = $this->auth->getCurrentSession();
            
            if ($currentSession) {
                $this->db->query("
                        DELETE
                        FROM
                            sessions
                        WHERE
                            id = ?
                    ",
                    [$currentSession['id']]
                );
                
                if (Helpers::isWebRequest()) {
                    Helpers::deleteAuthCookie();
                }
            }
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * DELETE /sessions/{session_id} - завершить конкретную сессию
         */
        public function terminateSession($sessionId) {
            Helpers::validateSessionId($sessionId);

            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $this->db->query("
                    DELETE
                    FROM
                        sessions
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                ",
                [$sessionId, $currentUser['id']]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }

        /**
         * DELETE /sessions - завершить ВСЕ сессии, кроме текущей
         */
        public function terminateAllOtherSessions() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            $currentSession = $this->auth->getCurrentSession();
            
            $this->db->query("
                    DELETE
                    FROM
                        sessions
                    WHERE
                        user_id = ?
                        AND
                        id != ?
                ",
                [$currentUser['id'], $currentSession['id']]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
