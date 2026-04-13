<?php
$host = 'sql105.infinityfree.com';
$dbname = 'if0_41627113_tropical_library';
$username = 'if0_41627113';
$password = 'ZXhiCI7fJx';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Подключаем систему ачивок
require_once __DIR__ . '/../includes/achievements.php';

?>