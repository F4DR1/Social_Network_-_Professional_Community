<?php

    namespace Core;

    use Predis\Client as PredisClient;

    class Redis {
        private static $redisChannel = 'events';


        private static ?PredisClient $instance = null;
        
        /**
         * Возвращает готовый экземпляр Predis\Client (ленивая инициализация)
         */
        private static function createRedis(): PredisClient {
            if (self::$instance === null) {
                self::$instance = new PredisClient([
                    'scheme' => 'tcp',
                    'host' => env('REDIS_HOST') ?: '127.0.0.1',
                    'port' => (int)(env('REDIS_PORT') ?: 6379),
                    'password' => env('REDIS_PASSWORD') ?: null,
                ]);
            }
            return self::$instance;
        }
        
        

        /**
         * Уведомляет пользователей чата о новом сообщении
         */
        public static function newMessage($chatId, $userId, $messageId) {
            $redis = self::createRedis();
            $redis->publish(self::$redisChannel, json_encode([
                'type' => 'new_message',
                'chatId' => $chatId,
                'userId' => $userId,
                'messageId' => $messageId
            ]));
        }

        /**
         * Уведомляет пользователя о новом уведомлении
         */
        public static function newNotification($userId, $notificationId) {
            $redis = self::createRedis();
            $redis->publish(self::$redisChannel, json_encode([
                'type' => 'new_notification',
                'userId' => $userId,
                'notificationId' => $notificationId
            ]));
        }
    }

?>
