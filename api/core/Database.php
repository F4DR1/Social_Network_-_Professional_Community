<?php
    class Database {
        private $pdo;
        
        /**
         * При создании объекта сразу подключаемся к БД
         */
        public function __construct() {
            $host = 'localhost';
            $dbname = 'social_network_pc';
            $user = 'root';
            $pass = '';
            
            try {
                $this->pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user,
                    $pass
                );
                // Режим ошибок - исключения
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die(json_encode(['error' => 'Ошибка подключения к БД']));
            }
        }
        

        /**
         * Выполнить запрос с параметрами
         */
        public function query($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
        
        /**
         * Получить одну запись
         */
        public function fetchOne($sql, $params = []) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        /**
         * Получить все записи
         */
        public function fetchAll($sql, $params = []) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        /**
         * Получить последний вставленный ID
         */
        public function lastInsertId() {
            return $this->pdo->lastInsertId();
        }

        /**
         * Начать транзакцию
         */
        public function beginTransaction() {
            return $this->pdo->beginTransaction();
        }
        
        /**
         * Подтвердить транзакцию
         */
        public function commit() {
            return $this->pdo->commit();
        }
        
        /**
         * Откатить транзакцию
         */
        public function rollBack() {
            return $this->pdo->rollBack();
        }
        
        /**
         * Выполнить транзакцию с колбэком
         */
        public function transaction(callable $callback) {
            $this->beginTransaction();
            try {
                $result = $callback($this);
                $this->commit();
                return $result;
            } catch (Exception $e) {
                $this->rollBack();
                throw $e;
            }
        }
    }
?>