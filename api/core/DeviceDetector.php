<?php
    class DeviceDetector {
        
        /**
         * Получает информацию об устройстве из User-Agent
         */
        public static function getDeviceInfo() {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            if (empty($userAgent)) {
                return [
                    'name' => 'Неизвестное устройство',
                    'type' => 'web'
                ];
            }
            
            // Определяем тип устройства
            if (self::isMobile($userAgent)) {
                return [
                    'type' => 'mobile',
                    'name' => self::parseMobileDevice($userAgent)
                ];
            } elseif (self::isTablet($userAgent)) {
                return [
                    'type' => 'tablet',
                    'name' => self::parseMobileDevice($userAgent)
                ];
            } else {
                return [
                    'type' => 'desktop',
                    'name' => self::parseDesktopDevice($userAgent)
                ];
            }
        }
        
        /**
         * Проверяет, является ли устройство мобильным
         */
        private static function isMobile($userAgent) {
            return preg_match('/(mobile|iphone|ipod|android|blackberry|opera mini|iemobile|wp desktop)/i', $userAgent);
        }
        
        /**
         * Проверяет, является ли устройство планшетом
         */
        private static function isTablet($userAgent) {
            return preg_match('/(tablet|ipad|kindle|playbook)/i', $userAgent);
        }
        
        /**
         * Парсит мобильное устройство
         */
        private static function parseMobileDevice($userAgent) {
            // iPhone
            if (preg_match('/iPhone OS (\d+)_(\d+)/i', $userAgent, $matches)) {
                return 'iPhone (iOS ' . $matches[1] . '.' . $matches[2] . ')';
            }
            if (strpos($userAgent, 'iPhone') !== false) {
                return 'iPhone';
            }
            
            // iPad
            if (preg_match('/iPad.*OS (\d+)_(\d+)/i', $userAgent, $matches)) {
                return 'iPad (iOS ' . $matches[1] . '.' . $matches[2] . ')';
            }
            if (strpos($userAgent, 'iPad') !== false) {
                return 'iPad';
            }
            
            // Android
            if (preg_match('/Android (\d+(?:\.\d+)?)/i', $userAgent, $matches)) {
                $androidVersion = $matches[1];
                
                if (preg_match('/; ?([^;]+) Build/', $userAgent, $modelMatches)) {
                    $model = trim($modelMatches[1]);
                    return $model . ' (Android ' . $androidVersion . ')';
                }
                return 'Android (версия ' . $androidVersion . ')';
            }
            
            return 'Мобильное устройство';
        }
        
        /**
         * Парсит десктопное устройство
         */
        private static function parseDesktopDevice($userAgent) {
            $os = self::parseOS($userAgent);
            $browser = self::parseBrowser($userAgent);
            
            return $os . ', ' . $browser;
        }
        
        /**
         * Определяет операционную систему
         */
        private static function parseOS($userAgent) {
            // Windows
            if (preg_match('/Windows NT (\d+\.\d+)/i', $userAgent, $matches)) {
                $windowsVersions = [
                    '10.0' => 'Windows 10/11',
                    '6.3' => 'Windows 8.1',
                    '6.2' => 'Windows 8',
                    '6.1' => 'Windows 7',
                    '6.0' => 'Windows Vista',
                    '5.2' => 'Windows XP x64',
                    '5.1' => 'Windows XP'
                ];
                $version = $matches[1];
                return $windowsVersions[$version] ?? 'Windows ' . $version;
            }
            
            // macOS
            if (preg_match('/Mac OS X (\d+)[_.](\d+)/i', $userAgent, $matches)) {
                return 'macOS ' . $matches[1] . '.' . $matches[2];
            }
            
            // Linux
            if (strpos($userAgent, 'Linux') !== false) {
                return 'Linux';
            }
            
            return 'Unknown OS';
        }
        
        /**
         * Определяет браузер
         */
        private static function parseBrowser($userAgent) {
            if (preg_match('/Chrome\/(\d+)/i', $userAgent, $matches)) {
                return 'Chrome ' . $matches[1];
            }
            if (preg_match('/Firefox\/(\d+)/i', $userAgent, $matches)) {
                return 'Firefox ' . $matches[1];
            }
            if (preg_match('/Safari\/(\d+)/i', $userAgent, $matches) && !preg_match('/Chrome/', $userAgent)) {
                return 'Safari';
            }
            if (preg_match('/Edge\/(\d+)/i', $userAgent, $matches)) {
                return 'Edge ' . $matches[1];
            }
            if (preg_match('/MSIE (\d+)/i', $userAgent, $matches)) {
                return 'Internet Explorer ' . $matches[1];
            }
            
            return 'Unknown Browser';
        }
        
        /**
         * Получает реальный IP пользователя (с учетом прокси)
         */
        public static function getClientIP() {
            $headers = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ];
            
            foreach ($headers as $header) {
                if (isset($_SERVER[$header])) {
                    $ip = $_SERVER[$header];
                    if (strpos($ip, ',') !== false) {
                        $ip = explode(',', $ip)[0];
                    }
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
            
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
?>
