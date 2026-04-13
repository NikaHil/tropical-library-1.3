<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    // Удаляем только если книга принадлежит пользователю
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $_SESSION['user_id']]);
}

header('Location: index.php');
exit;
?>