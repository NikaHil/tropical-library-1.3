<?php
require_once 'config/database.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = '🌿 Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '📧 Введите корректный email';
    } elseif (strlen($password) < 4) {
        $error = '🔐 Пароль должен быть не менее 4 символов';
    } elseif ($password !== $confirm) {
        $error = '❌ Пароли не совпадают';
    } else {
        // Проверяем, не заняты ли username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = '👤 Пользователь с таким именем или email уже существует';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash]);
            
            $success = '🎉 Регистрация успешна! Сейчас войдёшь...';
            header("refresh:2;url=login.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌺 Регистрация - Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="leaves-bg"></div>
    <div class="container" style="max-width: 500px;">
        <header>
            <h1>🌴 Добро пожаловать!</h1>
            <p>Создай свою тропическую библиотеку</p>
        </header>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST" class="book-form">
            <div class="form-group">
                <label>🌿 Имя пользователя</label>
                <input type="text" name="username" placeholder="Например: TropicalReader" required>
            </div>
            
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" placeholder="tropik@example.com" required>
            </div>
            
            <div class="form-group">
                <label>🔐 Пароль</label>
                <input type="password" name="password" placeholder="минимум 4 символа" required>
            </div>
            
            <div class="form-group">
                <label>🔐 Подтверди пароль</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">🍍 Зарегистрироваться</button>
                <a href="login.php" class="btn btn-link">Уже есть аккаунт? Войти</a>
            </div>
        </form>
    </div>
</body>
</html>