<?php
    class Helpers {
        
        // Форматирование телефона к единому стандарту
        public static function formatPhone($phone) {
            // Удаляем все кроме цифр
            $cleaned = preg_replace('/[^0-9]/', '', $phone);
            
            // Если начинается с 8, заменяем на 7 (для России)
            if (strlen($cleaned) == 11 && $cleaned[0] == '8') {
                $cleaned[0] = '7';
            }
            
            // Если 10 цифр и первая 9 (пример: 9123456789) - добавляем 7
            if (strlen($cleaned) == 10 && $cleaned[0] == '9') {
                $cleaned = '7' . $cleaned;
            }
            
            return $cleaned;
        }
        
        // Валидация email
        public static function validateEmail($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
        
        // Генерация случайного кода для SMS
        public static function generateSmsCode($length = 4) {
            return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        }
        
        // Проверка, является ли строка телефоном (примерная)
        public static function isPhone($string) {
            $digits = preg_replace('/[^0-9]/', '', $string);
            return strlen($digits) >= 10 && strlen($digits) <= 12;
        }
        
        // Безопасное экранирование данных для вывода в JSON
        public static function safeJson($data) {
            array_walk_recursive($data, function(&$item) {
                if (is_string($item)) {
                    // Удаляем управляющие символы
                    $item = preg_replace('/[\x00-\x1F\x7F]/u', '', $item);
                }
            });
            return $data;
        }
    }
?>
