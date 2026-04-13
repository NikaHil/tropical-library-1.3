<?php
require_once 'config/database.php';

// Общая статистика
$total = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$read = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'read'")->fetchColumn();
$reading = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'reading'")->fetchColumn();
$want = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'want'")->fetchColumn();

// Статистика по жанрам
$genreStats = $pdo->query("SELECT genre, COUNT(*) as count FROM books WHERE genre != '' GROUP BY genre ORDER BY count DESC LIMIT 5")->fetchAll();

// Средний рейтинг
$avgRating = $pdo->query("SELECT AVG(rating) FROM books WHERE rating IS NOT NULL")->fetchColumn();
$avgRating = $avgRating ? round($avgRating, 1) : 0;

// Самый читаемый автор
$topAuthor = $pdo->query("SELECT author, COUNT(*) as count FROM books GROUP BY author ORDER BY count DESC LIMIT 1")->fetch();

// Книг по годам
$yearStats = $pdo->query("SELECT year, COUNT(*) as count FROM books WHERE year IS NOT NULL GROUP BY year ORDER BY year DESC LIMIT 5")->fetchAll();

// ========== АЧИВКИ ==========
// Проверяем и обновляем ачивки
$progress = getAchievementProgress($pdo);
$achievements = getAllAchievements($pdo);

// Подсчёт полученных ачивок
$earnedCount = 0;
$totalCount = 0;
foreach ($achievements as $ach) {
    $totalCount++;
    if ($ach['earned']) $earnedCount++;
}
$percentComplete = $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Статистика - Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Дополнительные стили для ачивок */
        .achievements-section {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8d6 100%);
            border-radius: 20px;
            padding: 25px;
            margin-top: 35px;
            border: 2px solid #FFB347;
        }
        
        .achievements-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .achievements-header h2 {
            color: #1a472a;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .achievements-header p {
            color: #2d6a4f;
            font-size: 14px;
        }
        
        .progress-bar-container {
            background: #e0d6c8;
            border-radius: 30px;
            height: 12px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            background: linear-gradient(90deg, #FF6B35, #FFB347);
            width: <?= $percentComplete ?>%;
            height: 100%;
            border-radius: 30px;
            transition: width 0.5s ease;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #1a472a;
            font-weight: 500;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .achievement-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid #ffe0b5;
            position: relative;
            overflow: hidden;
        }
        
        .achievement-card.earned {
            border-color: #FF6B35;
            background: linear-gradient(135deg, #fff 0%, #fff5e6 100%);
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.2);
        }
        
        .achievement-card.earned::before {
            content: "🏆";
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 50px;
            opacity: 0.15;
            transform: rotate(15deg);
        }
        
        .achievement-card.locked {
            opacity: 0.7;
            filter: grayscale(0.2);
        }
        
        .achievement-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        
        .achievement-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a472a;
            margin-bottom: 8px;
        }
        
        .achievement-desc {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .achievement-status {
            font-size: 13px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 50px;
            display: inline-block;
        }
        
        .status-earned {
            background: #2ECC71;
            color: white;
        }
        
        .status-locked {
            background: #e0d6c8;
            color: #8b7a66;
        }
        
        .achievement-progress {
            margin-top: 12px;
            font-size: 12px;
            color: #FF6B35;
            font-weight: 500;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ffe0b5;
        }
        
        .stat-badge {
            background: #f0e8dd;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            color: #1a472a;
        }
        
        .new-achievement-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: slideIn 0.5s ease, fadeOut 0.5s ease 3s forwards;
            cursor: pointer;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            to {
                transform: translateX(100%);
                opacity: 0;
                visibility: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="leaves-bg"></div>
    
    <div class="container">
        <header>
            <h1>🌴📊 Тропическая статистика</h1>
            <a href="index.php" class="btn btn-secondary">← На главную</a>
        </header>
        
        <!-- Основные статистические карточки -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-label">Всего книг</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number"><?= $avgRating ?></div>
                <div class="stat-label">Средний рейтинг</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✍️</div>
                <div class="stat-number"><?= htmlspecialchars(mb_substr($topAuthor['author'] ?? '-', 0, 15)) ?></div>
                <div class="stat-label">Любимый автор</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🍍</div>
                <div class="stat-number"><?= $read ?></div>
                <div class="stat-label">Прочитано книг</div>
            </div>
        </div>
        
        <!-- Графики -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>📖 Статус книг</h3>
                <canvas id="statusChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>🎭 Топ 5 жанров</h3>
                <canvas id="genreChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>📅 Книги по годам</h3>
                <canvas id="yearChart" width="400" height="300"></canvas>
            </div>
        </div>
        
        <!-- БЛОК АЧИВОК -->
        <div class="achievements-section">
            <div class="achievements-header">
                <h2>🏆 Достижения читателя</h2>
                <p>Открой все ачивки, читая книги разных жанров</p>
                
                <div class="progress-bar-container">
                    <div class="progress-bar-fill"></div>
                </div>
                <div class="progress-stats">
                    <span>📊 Прогресс: <?= $earnedCount ?> / <?= $totalCount ?> ачивок</span>
                    <span>🏆 <?= $percentComplete ?>% завершено</span>
                </div>
            </div>
            
            <div class="achievements-grid">
                <?php foreach ($achievements as $ach): 
                    $isEarned = $ach['earned'];
                    $progressText = '';
                    
                    if ($ach['required_books'] !== null) {
                        $current = $progress['total_read'];
                        $required = $ach['required_books'];
                        $progressText = "📚 $current / $required книг";
                    } elseif ($ach['required_genres'] !== null) {
                        $current = $progress['unique_genres'];
                        $required = $ach['required_genres'];
                        $progressText = "🎭 $current / $required жанров";
                    }
                ?>
                    <div class="achievement-card <?= $isEarned ? 'earned' : 'locked' ?>">
                        <div class="achievement-icon"><?= $ach['icon'] ?></div>
                        <div class="achievement-name"><?= htmlspecialchars($ach['achievement_name']) ?></div>
                        <div class="achievement-desc"><?= htmlspecialchars($ach['achievement_desc']) ?></div>
                        
                        <?php if ($isEarned): ?>
                            <span class="achievement-status status-earned">
                                ✅ Получено <?= date('d.m.Y', strtotime($ach['earned_at'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="achievement-status status-locked">🔒 Не получено</span>
                            <div class="achievement-progress"><?= $progressText ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="stats-row">
                <div class="stat-badge">📖 Всего прочитано: <?= $progress['total_read'] ?> книг</div>
                <div class="stat-badge">🎭 Разных жанров: <?= $progress['unique_genres'] ?></div>
                <div class="stat-badge">🎯 Цель: 50 книг и 5 жанров</div>
            </div>
        </div>
    </div>
    
    <script>
        // Графики
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['🍍 Прочитано', '🌺 Читаю', '🥥 Хочу'],
                datasets: [{
                    data: [<?= $read ?>, <?= $reading ?>, <?= $want ?>],
                    backgroundColor: ['#FF6B35', '#FFB347', '#2ECC71'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
        
        new Chart(document.getElementById('genreChart'), {
            type: 'bar',
            data: {
                labels: [<?php foreach ($genreStats as $g) echo "'" . addslashes($g['genre']) . "',"; ?>],
                datasets: [{
                    label: 'Количество книг',
                    data: [<?php foreach ($genreStats as $g) echo $g['count'] . ','; ?>],
                    backgroundColor: '#FF6B35',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        
        new Chart(document.getElementById('yearChart'), {
            type: 'line',
            data: {
                labels: [<?php foreach ($yearStats as $y) echo $y['year'] . ','; ?>],
                datasets: [{
                    label: 'Книг издано',
                    data: [<?php foreach ($yearStats as $y) echo $y['count'] . ','; ?>],
                    borderColor: '#2ECC71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { responsive: true }
        });
    </script>
    
    <script src="js/script.js"></script>
</body>
</html>