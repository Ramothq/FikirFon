<?php
session_start();
require 'db_connect.php'; // Veritabanı bağlantısı

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, role, bio, profile_image_url FROM users WHERE id = $1";
$result = pg_query_params($conn, $query, array($user_id));
$user = pg_fetch_assoc($result);

// Kullanıcının fikirlerini al
$query = "SELECT i.*, c.name as category_name,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id) as support_count,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id AND user_id = $1) as user_supported
          FROM ideas i 
          LEFT JOIN categories c ON i.category_id = c.id
          WHERE i.user_id = $1 
          ORDER BY i.created_at DESC";
$ideas_result = pg_query_params($conn, $query, array($user_id));

// Kullanıcının desteklediği fikirleri al
$query = "SELECT i.*, c.name as category_name,
          u.first_name, u.last_name, u.role,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id) as support_count,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id AND user_id = $1) as user_supported
          FROM ideas i 
          LEFT JOIN categories c ON i.category_id = c.id
          JOIN users u ON i.user_id = u.id
          JOIN supports s ON i.id = s.idea_id
          WHERE s.user_id = $1
          ORDER BY i.created_at DESC";
$supported_result = pg_query_params($conn, $query, array($user_id));

// İstatistikleri al
$query = "SELECT 
          (SELECT COUNT(*) FROM ideas WHERE user_id = $1) as idea_count,
          (SELECT COUNT(*) FROM supports WHERE user_id = $1) as support_count";
$stats_result = pg_query_params($conn, $query, array($user_id));
$stats = pg_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto 2rem;
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
            flex-shrink: 0;
            overflow: hidden;
        }

        .profile-avatar img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            flex: 1;
            color: var(--text-primary);
            padding-bottom: 1rem;
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

        .profile-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .profile-actions a {
            text-decoration: none;
        }

        .profile-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--white);
            color: var(--primary-color);
        }

        .profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .profile-btn svg {
            width: 20px;
            height: 20px;
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
            position: relative;
        }

        .idea-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .idea-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
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

        .idea-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .idea-action-btn {
            padding: 0.5rem;
            
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .idea-action-btn:hover {
            background: var(--primary-light);
            color: var(--white);
            border-color: var(--primary-light);
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
            display: block;
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

            .profile-actions {
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

        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            margin-top: 1rem;
            border: none;
            cursor: pointer;
        }

        .share-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .share-btn svg {
            width: 16px;
            height: 16px;
        }

        .edit-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .edit-profile-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .edit-profile-btn svg {
            width: 16px;
            height: 16px;
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

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-header-bg"></div>
            <div class="profile-content">
                <div class="profile-avatar">
                    <?php if (!empty($user['profile_image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image_url']); ?>" alt="Profil Resmi">
                    <?php else: ?>
                        <svg width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5.52 19c.64-2.2 1.84-3.3 4.4-3.3h5.16c2.56 0 3.76 1.1 4.4 3.3"></path>
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p class="profile-role"><?php echo htmlspecialchars($user['role']); ?></p>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-value"><?php echo $stats['idea_count']; ?></span>
                            <span class="stat-label">Fikir</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo $stats['support_count']; ?></span>
                            <span class="stat-label">Destek</span>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <a href="share_idea.php" class="share-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            Fikir Paylaş
                        </a>
                        <a href="edit_profile.php" class="edit-profile-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Profili Düzenle
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-tabs">
            <div class="profile-tab active" onclick="showTab('ideas')">Fikirlerim</div>
            <div class="profile-tab" onclick="showTab('supported')">Desteklediklerim</div>
        </div>

        <div id="ideas-tab" class="ideas-grid">
            <?php if (pg_num_rows($ideas_result) > 0): ?>
                <?php while ($idea = pg_fetch_assoc($ideas_result)): ?>
                    <div class="idea-card">
                        <div class="idea-card-header">
                            <h3 class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></h3>
                            <div class="idea-actions">
                                <a href="edit_idea.php?id=<?php echo $idea['id']; ?>" class="idea-action-btn edit-btn">Düzenle</a>
                                <button class="idea-action-btn delete-btn" data-id="<?php echo $idea['id']; ?>">Sil</button>
                            </div>
                        </div>
                        <h3 class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></h3>
                        <p class="idea-description"><?php echo htmlspecialchars($idea['description']); ?></p>
                        <div class="idea-meta">
                            <span class="idea-category"><?php echo htmlspecialchars($idea['category_name'] ?? 'Kategori Yok'); ?></span>
                            <span class="idea-date"><?php echo date('d.m.Y', strtotime($idea['created_at'])); ?></span>
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
                    <p>İlk fikrinizi paylaşmak için "Fikir Paylaş" butonuna tıklayın.</p>
                    <a href="share_idea.php" class="btn-primary">Fikir Paylaş</a>
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
                            <span class="idea-category"><?php echo htmlspecialchars($idea['category_name'] ?? 'Kategori Yok'); ?></span>
                            <span class="idea-date"><?php echo date('d.m.Y', strtotime($idea['created_at'])); ?></span>
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
                    <p>Fikirleri desteklemek için ana sayfaya gidin.</p>
                    <a href="trending.php" class="btn-primary">Fikirleri Keşfet</a>
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

    function editProfile() {
        // Profil düzenleme modalını aç
        alert('Profil düzenleme özelliği yakında eklenecek!');
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
                                ${user.first_name} ${user.last_name} (${user.role})
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

    // Silme butonlarına event listener ekle
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const ideaId = this.dataset.id;
            if (confirm('Bu fikri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                const formData = new FormData();
                formData.append('id', ideaId);

                fetch('delete_idea.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Fikri sayfadan kaldır
                        this.closest('.idea-card').remove();
                    } else {
                        alert(data.error || 'Fikir silinirken bir hata oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('Fikir silinirken bir hata oluştu. Lütfen tekrar deneyin.');
                });
            }
        });
    });
    </script>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; <?php echo date('Y'); ?> FikirFon. Tüm Hakları Saklıdır.</p>
    </div>
</footer>

</body>
</html>
