<?php
    // Путь к базе
    $dbFile = __DIR__ . '/data.db';

    // Создаем подключение
    global $db;
    $db = new SQLite3($dbFile);
?>
