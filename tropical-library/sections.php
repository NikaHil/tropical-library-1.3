<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$section = $_GET['section'] ?? 'all';

$section_title = '';
$status_filter = '';

switch ($section) {
    case 'read':
        $section_title = '🍍 Прочитанные книги';
        $status_filter = 'read';
        break;
    case 'reading':
        $section_title = '🌺 Читаю сейчас';
        $status_filter = 'reading';
        break;
    case 'want':
        $section_title = '🥥 Хочу прочитать';
        $status_filter = 'want';
        break;
    default:
        $section_title = '🌴 Все книги';
        $status_filter = 'all';
        $section = 'all';
}

if ($status_filter == 'all') {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE user_id = ? AND status = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id, $status_filter]);
}
$books = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_all = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'read'");
$stmt->execute([$user_id]);
$total_read = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'reading'");
$stmt->execute([$user_id]);
$total_reading = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'want'");
$stmt->execute([$user_id]);
$total_want = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $section_title ?> - Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Кнопка "На главную" - тропический стиль */
        .btn-home {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 50%, #F39C12 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .btn-home::before {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            transition: all 0.3s ease;
            opacity: 1;
        }
        
        .btn-home span {
            margin-left: 25px;
        }
        
        .btn-home:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.6);
            background: linear-gradient(135deg, #FF8C42 0%, #F39C12 50%, #FF6B35 100%);
        }
        
        .btn-home:hover::before {
            animation: bounce 0.5s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(-50%) rotate(0deg); }
            50% { transform: translateY(-50%) rotate(15deg); }
        }
        
        .btn-home:active {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="leaves-bg"></div>
    
    <div class="container">
        <header>
            <div class="header-title">
                <h1>🌴 Тропическая библиотека</h1>
                <p><?= $section_title ?></p>
            </div>
            <div class="header-buttons">
                <a href="index.php" class="btn-home"><span>🏠 На главную</span></a>
                <a href="add.php" class="btn btn-primary">🍍 + Добавить книгу</a>
                <a href="logout.php" class="btn btn-secondary">🚪 Выйти</a>
            </div>
        </header>

        <!-- Карточки статистики -->
        <div class="stats-cards">
            <a href="sections.php?section=all" class="stat-card <?= $section == 'all' ? 'active' : '' ?>">
                <div class="stat-icon">🌴</div>
                <div class="stat-number"><?= $total_all ?></div>
                <div class="stat-label">Всего книг</div>
            </a>
            <a href="sections.php?section=read" class="stat-card <?= $section == 'read' ? 'active' : '' ?>">
                <div class="stat-icon">🍍</div>
                <div class="stat-number"><?= $total_read ?></div>
                <div class="stat-label">Прочитано</div>
            </a>
            <a href="sections.php?section=reading" class="stat-card <?= $section == 'reading' ? 'active' : '' ?>">
                <div class="stat-icon">🌺</div>
                <div class="stat-number"><?= $total_reading ?></div>
                <div class="stat-label">Читаю</div>
            </a>
            <a href="sections.php?section=want" class="stat-card <?= $section == 'want' ? 'active' : '' ?>">
                <div class="stat-icon">🥥</div>
                <div class="stat-number"><?= $total_want ?></div>
                <div class="stat-label">Хочу</div>
            </a>
        </div>

        <!-- Список книг -->
        <div class="books-grid">
            <?php if (count($books) == 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <?php if ($section == 'read'): ?>
                            🍍📖😢
                        <?php elseif ($section == 'reading'): ?>
                            🌺📖😢
                        <?php elseif ($section == 'want'): ?>
                            🥥📖😢
                        <?php else: ?>
                            📚🌴😢
                        <?php endif; ?>
                    </div>
                    <h3>
                        <?php if ($section == 'read'): ?>
                            Нет прочитанных книг
                        <?php elseif ($section == 'reading'): ?>
                            Ты ничего не читаешь
                        <?php elseif ($section == 'want'): ?>
                            Список «Хочу» пуст
                        <?php else: ?>
                            В библиотеке нет книг
                        <?php endif; ?>
                    </h3>
                    <a href="add.php" class="btn btn-primary">➕ Добавить книгу</a>
                </div>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <div class="book-status status-<?= $book['status'] ?>">
                            <?php
                            $status_text = [
                                'read' => '🍍 Прочитано',
                                'reading' => '🌺 Читаю',
                                'want' => '🥥 Хочу'
                            ];
                            echo $status_text[$book['status']];
                            ?>
                        </div>
                        
                        <div class="book-content">
                            <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                            <p class="book-author">✍️ <?= htmlspecialchars($book['author']) ?></p>
                            
                            <div class="book-details">
                                <?php if ($book['genre']): ?>
                                    <span class="book-tag">🌿 <?= htmlspecialchars($book['genre']) ?></span>
                                <?php endif; ?>
                                <?php if ($book['year']): ?>
                                    <span class="book-tag">📅 <?= $book['year'] ?></span>
                                <?php endif; ?>
                                <?php if ($book['rating']): ?>
                                    <span class="book-rating">⭐ <?= $book['rating'] ?>/10</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($book['review']): ?>
                                <p class="book-review">💭 <?= htmlspecialchars(mb_substr($book['review'], 0, 100)) ?>...</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-actions">
                            <a href="edit.php?id=<?= $book['id'] ?>" class="btn-icon btn-edit">✏️ Редактировать</a>
                            <a href="delete.php?id=<?= $book['id'] ?>" class="btn-icon btn-delete" 
                               onclick="return confirm('Удалить книгу «<?= htmlspecialchars($book['title']) ?>»?')">🗑️ Удалить</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>