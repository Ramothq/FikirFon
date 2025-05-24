<?php
session_start();
require 'db_connect.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Profil ID'sini al
$profile_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$profile_id) {
    header("Location: trending.php");
    exit;
}

// Profil sahibinin bilgilerini al
$query = "SELECT first_name, last_name, role, bio FROM users WHERE id = $1";
$result = pg_query_params($conn, $query, array($profile_id));
$user = pg_fetch_assoc($result);

if (!$user) {
    header("Location: trending.php");
    exit;
}

// Kullanıcının fikirlerini al
$query = "SELECT i.*, 
                (SELECT COUNT(*) FROM supports WHERE idea_id = i.id) as support_count,
                (SELECT COUNT(*) FROM supports WHERE idea_id = i.id AND user_id = $1) as user_supported
                FROM ideas i 
                WHERE i.user_id = $2 
                ORDER BY i.created_at DESC";
$ideas_result = pg_query_params($conn, $query, array($_SESSION['user_id'], $profile_id));

// Kullanıcının desteklediği fikirleri al
$query = "SELECT i.*, 
          u.first_name, u.last_name, u.role,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id) as support_count,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id AND user_id = $1) as user_supported
          FROM ideas i 
          JOIN users u ON i.user_id = u.id
          JOIN supports s ON i.id = s.idea_id
          WHERE s.user_id = $2
          ORDER BY i.created_at DESC";
$supported_result = pg_query_params($conn, $query, array($_SESSION['user_id'], $profile_id));

// İstatistikleri al
$query = "SELECT 
          (SELECT COUNT(*) FROM ideas WHERE user_id = $1) as idea_count,
          (SELECT COUNT(*) FROM supports WHERE user_id = $1) as support_count";
$stats_result = pg_query_params($conn, $query, array($profile_id));
$stats = pg_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .user-profile-container {
            max-width: 1200px;
            margin: 6rem auto 2rem;
            padding: 0 2rem;
        }

        .profile-header {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            z-index: 0;
        }

        .profile-content {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 2rem;
            align-items: flex-end;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid var(--white);
            box-shadow: var(--shadow-md);
            object-fit: cover;
            background: var(--white);
        }

        .profile-info {
            flex: 1;
            color: var(--white);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .profile-bio {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
            margin: 1rem 0;
            padding: 1.5rem;
            background: rgba(107, 70, 193, 0.05);
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .profile-bio::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(107, 70, 193, 0.1) 0%, rgba(237, 137, 54, 0.1) 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .profile-bio:hover::before {
            opacity: 1;
        }

        .profile-bio:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
        }

        .profile-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .profile-tab {
            padding: 1rem 2rem;
            background: var(--white);
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-secondary);
        }

        .profile-tab.active {
            background: var(--primary-color);
            color: var(--white);
        }

        .profile-tab:hover:not(.active) {
            background: var(--primary-light);
            color: var(--white);
        }

        .ideas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .idea-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(107, 70, 193, 0.1);
            animation: fadeIn 0.5s ease;
        }

        .idea-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .idea-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .idea-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .idea-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .idea-category {
            background: rgba(107, 70, 193, 0.1);
            color: var(--primary-color);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
        }

        .idea-date {
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .idea-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .support-count {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .profile-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .profile-tabs {
                flex-wrap: wrap;
            }

            .profile-tab {
                flex: 1;
                text-align: center;
            }
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            width: 400px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            margin-top: 0.5rem;
        }

        .search-results.visible {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .search-results div {
            padding: 1rem;
            border-bottom: 1px solid rgba(107, 70, 193, 0.1);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-results div:hover {
            background: rgba(107, 70, 193, 0.1);
        }

        .search-results .user-link {
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .search-results .user-link:hover {
            background: rgba(107, 70, 193, 0.1);
        }

        .search-results .user-info {
            display: flex;
            flex-direction: column;
        }

        .search-results .user-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .search-results .user-role {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav>
        <div class="nav-inner-container">
            <div class="nav-left">
                <h1>FikirFon</h1>
            </div>
            <div class="nav-center">
                <input type="text" class="search-box" placeholder="Kullanıcı, fikir veya etiket ara...">
                <div id="search-results" class="search-results"></div>
            </div>
            <div class="nav-right">
                <a href="trending.php" class="nav-link">Öne Çıkanlar</a>
                <a href="profile.php" class="profile-link">Profilim</a>
                <a href="logout.php" class="logout-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Çıkış Yap
                </a>
            </div>
        </div>
    </nav>

    <div class="user-profile-container">
        <div class="profile-header">
            <div class="profile-content">
                <img src="../images/default-profile.jpg" alt="Profil Resmi" class="profile-avatar">
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p class="profile-role"><?php echo htmlspecialchars($user['role']); ?></p>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <strong><?php echo $stats['idea_count']; ?></strong> Fikir
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $stats['support_count']; ?></strong> Destek
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-tabs">
            <div class="profile-tab active" onclick="showTab('ideas')">Fikirler</div>
            <div class="profile-tab" onclick="showTab('supported')">Desteklenenler</div>
        </div>

        <div id="ideas-tab" class="ideas-grid">
            <?php if (pg_num_rows($ideas_result) > 0): ?>
                <?php while ($idea = pg_fetch_assoc($ideas_result)): ?>
                    <div class="idea-card">
                        <h3 class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></h3>
                        <p class="idea-description"><?php echo htmlspecialchars($idea['description']); ?></p>
                        <div class="idea-meta">
                            <span class="idea-category"><?php echo htmlspecialchars($idea['category']); ?></span>
                            <span class="idea-date"><?php echo date('d.m.Y', strtotime($idea['created_at'])); ?></span>
                        </div>
                        <div class="idea-actions">
                            <span class="support-count"><?php echo $idea['support_count']; ?> destek</span>
                            <button class="support-btn <?php echo $idea['user_supported'] > 0 ? 'supported' : ''; ?>" 
                                    data-idea-id="<?php echo $idea['id']; ?>">
                                <?php echo $idea['user_supported'] > 0 ? '✓ Desteklendi' : '🤝 Destekle'; ?>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3>Henüz fikir paylaşılmamış</h3>
                    <p>Bu kullanıcı henüz hiç fikir paylaşmamış.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="supported-tab" class="ideas-grid" style="display: none;">
            <?php if (pg_num_rows($supported_result) > 0): ?>
                <?php while ($idea = pg_fetch_assoc($supported_result)): ?>
                    <div class="idea-card">
                        <h3 class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></h3>
                        <p class="idea-description"><?php echo htmlspecialchars($idea['description']); ?></p>
                        <div class="idea-meta">
                            <span class="idea-category"><?php echo htmlspecialchars($idea['category']); ?></span>
                            <span class="idea-date"><?php echo date('d.m.Y', strtotime($idea['created_at'])); ?></span>
                        </div>
                        <div class="idea-actions">
                            <span class="support-count"><?php echo $idea['support_count']; ?> destek</span>
                            <button class="support-btn <?php echo $idea['user_supported'] > 0 ? 'supported' : ''; ?>" 
                                    data-idea-id="<?php echo $idea['id']; ?>">
                                <?php echo $idea['user_supported'] > 0 ? '✓ Desteklendi' : '🤝 Destekle'; ?>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3>Henüz hiç fikir desteklenmemiş</h3>
                    <p>Bu kullanıcı henüz hiç fikir desteklememiş.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function showTab(tabName) {
        const tabs = document.querySelectorAll('.profile-tab');
        const tabContents = document.querySelectorAll('.ideas-grid');
        
        tabs.forEach(tab => tab.classList.remove('active'));
        tabContents.forEach(content => content.style.display = 'none');
        
        document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
        document.getElementById(`${tabName}-tab`).style.display = 'grid';
    }

    // Arama işlevselliği
        const searchInput = document.querySelector(".search-box");
        const resultsBox = document.getElementById("search-results");

        searchInput.addEventListener("input", function() {
            const query = this.value.trim();
            if (query.length === 0) {
                resultsBox.classList.remove('visible');
                resultsBox.innerHTML = "";
                return;
            }

            fetch(`search_user.php?query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    resultsBox.innerHTML = "";
                    if (data.error) {
                        resultsBox.classList.add('visible');
                        resultsBox.innerHTML = `<div>⛔ Hata: ${data.error}</div>`;
                        return;
                    }

                    if (data.length === 0) {
                        resultsBox.classList.add('visible');
                        resultsBox.innerHTML = "<div>⛔ Kullanıcı bulunamadı.</div>";
                    } else {
                        resultsBox.classList.add('visible');
                        data.forEach(user => {
                            const div = document.createElement("div");
                            div.innerHTML = `
                                <a href="user_profile.php?id=${user.id}" class="user-link">
                                <img src="../images/default-profile.jpg" alt="Profil Resmi" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div class="user-info">
                                    <span class="user-name">${user.first_name} ${user.last_name}</span>
                                    <span class="user-role">${user.role}</span>
                                </div>
                                </a>
                            `;
                            div.addEventListener('click', function(e) {
                                e.preventDefault();
                                window.location.href = `user_profile.php?id=${user.id}`;
                            });
                            resultsBox.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error("Fetch hatası:", error);
                    resultsBox.classList.add('visible');
                    resultsBox.innerHTML = "<div>⛔ Bir hata oluştu. Lütfen tekrar deneyin.</div>";
                });
        });

        // Arama kutusu dışına tıklandığında sonuçları gizle
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !resultsBox.contains(event.target)) {
                resultsBox.classList.remove('visible');
            }
        });

    // Destekleme işlemi için JavaScript (Eğer bu sayfada destek butonu varsa)
    // Bu kısım user_profile.php'nin ihtiyacına göre ayarlanmalı
    function toggleSupport(ideaId) {
        // Destekleme/Geri Çekme AJAX çağrısı burada yapılacak
        alert('Destekleme özelliği yakında eklenecek!');
    }

    // Takip etme işlemi için JavaScript (Eğer bu sayfada takip butonu varsa)
    function toggleFollow(userId) {
        // Takip etme/Geri Çekme AJAX çağrısı burada yapılacak
        alert('Takip etme özelliği yakında eklenecek!');
    }
    </script>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> FikirFon. Tüm Hakları Saklıdır.</p>
        </div>
    </footer>
</body>
</html> 