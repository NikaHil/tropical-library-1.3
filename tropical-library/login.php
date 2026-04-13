<?php
require_once 'config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = '🌿 Заполни все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = '❌ Неверное имя пользователя/email или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🍍 Вход - Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="leaves-bg"></div>
    <div class="container" style="max-width: 500px;">
        <header>
            <h1>🍍 Вход в библиотеку</h1>
            <p>Твои книги под шум океана</p>
        </header>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="book-form">
            <div class="form-group">
                <label>🌿 Имя пользователя или Email</label>
                <input type="text" name="login" placeholder="TropicalReader" required>
            </div>
            
            <div class="form-group">
                <label>🔐 Пароль</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">🌺 Войти</button>
                <a href="register.php" class="btn btn-link">Нет аккаунта? Регистрация</a>
            </div>
        </form>
    </div>
</body>
</html>