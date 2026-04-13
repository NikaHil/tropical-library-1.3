<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем параметры фильтрации
$status = $_GET['status'] ?? 'all';
$genre = $_GET['genre'] ?? '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = strip_tags($search);

// Строим запрос
$sql = "SELECT * FROM books WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if ($status != 'all') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}

if ($genre != '') {
    $sql .= " AND genre LIKE :genre";
    $params[':genre'] = "%$genre%";
}

if ($search != '') {
    $search_safe = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $search);
    $sql .= " AND (title LIKE :search OR author LIKE :search)";
    $params[':search'] = "%$search_safe%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$books = $stmt->fetchAll();

// Статистика
$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ?");
$stmt->execute([$user_id]);
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'read'");
$stmt->execute([$user_id]);
$read = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'reading'");
$stmt->execute([$user_id]);
$reading = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'want'");
$stmt->execute([$user_id]);
$want = $stmt->fetchColumn();

// Список жанров
$stmt = $pdo->prepare("SELECT DISTINCT genre FROM books WHERE genre != '' AND user_id = ? ORDER BY genre");
$stmt->execute([$user_id]);
$genres = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌺 Тропическая библиотека - <?= htmlspecialchars($_SESSION['username']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="leaves-bg"></div>
    
    <div class="container">
        <header>
            <div class="header-title">
                <h1>🌴 Тропическая библиотека</h1>
                <p>Привет, <?= htmlspecialchars($_SESSION['username']) ?>! 📚</p>
            </div>
            <div class="header-buttons">
                <a href="stats.php" class="btn btn-stats">📊 Статистика</a>
                <a href="add.php" class="btn btn-primary">🍍 + Добавить книгу</a>
                <a href="logout.php" class="btn btn-secondary">🚪 Выйти</a>
            </div>
        </header>

        <!-- Карточки статистики (кликабельные) -->
        <div class="stats-cards">
            <a href="sections.php?section=all" class="stat-card">
                <div class="stat-icon">🌴</div>
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-label">Всего книг</div>
            </a>
            <a href="sections.php?section=read" class="stat-card">
                <div class="stat-icon">🍍</div>
                <div class="stat-number"><?= $read ?></div>
                <div class="stat-label">Прочитано</div>
            </a>
            <a href="sections.php?section=reading" class="stat-card">
                <div class="stat-icon">🌺</div>
                <div class="stat-number"><?= $reading ?></div>
                <div class="stat-label">Читаю</div>
            </a>
            <a href="sections.php?section=want" class="stat-card">
                <div class="stat-icon">🥥</div>
                <div class="stat-number"><?= $want ?></div>
                <div class="stat-label">Хочу</div>
            </a>
        </div>
        
        <!-- Виджет последних ачивок -->
<?php
$achievements = getAllAchievements($pdo);
$recentEarned = array_filter($achievements, function($a) {
    return $a['earned'];
});
$earnedCount = count($recentEarned);
$totalCount = count($achievements);
?>
<div class="achievements-widget" style="background: linear-gradient(135deg, #2d6a4f, #1a472a); border-radius: 20px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <span style="font-size: 40px;">🏆</span>
        <div>
            <h3 style="color: white; margin: 0;">Твои достижения</h3>
            <p style="color: #FFB347; margin: 0; font-size: 14px;"><?= $earnedCount ?> из <?= $totalCount ?> ачивок получено</p>
        </div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php foreach ($recentEarned as $ach): ?>
            <span style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 50px; color: white; font-size: 13px;">
                <?= $ach['icon'] ?> <?= htmlspecialchars($ach['achievement_name']) ?>
            </span>
        <?php endforeach; ?>
        <?php if ($earnedCount == 0): ?>
            <span style="color: #FFB347; font-size: 14px;">📖 Читай книги, чтобы получать достижения!</span>
        <?php endif; ?>
    </div>
    <a href="stats.php#achievements" style="background: #FF6B35; color: white; padding: 8px 20px; border-radius: 50px; text-decoration: none; font-weight: 600;">📊 Все ачивки →</a>
</div>

        <!-- Фильтры -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <select name="status" class="filter-select">
                        <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>🌊 Все статусы</option>
                        <option value="read" <?= $status == 'read' ? 'selected' : '' ?>>🍍 Прочитано</option>
                        <option value="reading" <?= $status == 'reading' ? 'selected' : '' ?>>🌺 Читаю</option>
                        <option value="want" <?= $status == 'want' ? 'selected' : '' ?>>🥥 Хочу</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="genre" class="filter-select">
                        <option value="">🌿 Все жанры</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= htmlspecialchars($g['genre']) ?>" <?= $genre == $g['genre'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['genre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group search-group">
                    <input type="text" name="search" placeholder="🔍 Поиск по книге или автору..." 
                           value="<?= htmlspecialchars($search) ?>" class="search-input">
                </div>

                <button type="submit" class="btn btn-secondary">🌿 Найти</button>
                <a href="index.php" class="btn btn-link">Сбросить</a>
            </form>
        </div>

        <!-- Список книг -->
        <div class="books-grid">
            <?php if (count($books) == 0): ?>
                <?php if (!empty($search) || $status != 'all' || !empty($genre)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔍🌴😢</div>
                        <h3>Ничего не найдено</h3>
                        <?php if (!empty($search)): ?>
                            <p>По запросу «<strong><?= htmlspecialchars($search) ?></strong>» ничего нет</p>
                        <?php else: ?>
                            <p>По выбранным фильтрам ничего нет</p>
                        <?php endif; ?>
                        <div class="empty-buttons">
                            <a href="index.php" class="btn btn-secondary">🌿 Сбросить фильтры</a>
                            <a href="add.php" class="btn btn-primary">🍍 Добавить книгу</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🍍📖🌺</div>
                        <h3>В твоей библиотеке пока пусто</h3>
                        <p>Добавь первую книгу и отправляйся в чтение</p>
                        <a href="add.php" class="btn btn-primary">➕ Добавить книгу</a>
                    </div>
                <?php endif; ?>
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
   							 <?php if ($book['genre']): 
       							 // Подключаем иконки для жанров
        					require_once 'config/genres.php';
        					$icon = getGenreIcon($book['genre'], $genreIcons);
    						?>
       							 <span class="book-tag"><?= $icon ?> <?= htmlspecialchars($book['genre']) ?></span>
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