<?php
session_start();
require 'db_connect.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, role FROM users WHERE id = $1";
$result = pg_query_params($conn, $query, array($user_id));
$user = pg_fetch_assoc($result);

// Kategori filtresi
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Fikirleri getir
$query = "SELECT i.*, c.name as category_name,
          u.first_name, u.last_name, u.role,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id) as support_count,
          (SELECT COUNT(*) FROM supports WHERE idea_id = i.id AND user_id = $1) as user_supported
          FROM ideas i 
          LEFT JOIN categories c ON i.category_id = c.id
          JOIN users u ON i.user_id = u.id";

if ($category) {
    $query .= " WHERE c.name = $2";
    $params = array($user_id, $category);
} else {
    $params = array($user_id);
}

$query .= " ORDER BY support_count DESC, i.created_at DESC";
$result = pg_query_params($conn, $query, $params);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öne Çıkan Fikirler - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .filter-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            margin: 2rem auto;
            max-width: 1200px;
            animation: fadeIn 0.5s ease;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .filter-btn {
            padding: 0.8rem 1.5rem;
            border: 2px solid var(--primary-light);
            border-radius: 50px;
            background: var(--white);
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            text-decoration: none;
        }

        .filter-btn:hover {
            background: var(--primary-light);
            color: var(--white);
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .idea-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .idea-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(107, 70, 193, 0.1);
            animation: fadeIn 0.5s ease;
        }

        .idea-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .user-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .user-role {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .support-count {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: var(--primary-light);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .idea-title {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .idea-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .idea-category {
            display: inline-block;
            background: rgba(107, 70, 193, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .idea-image {
            width: 100%;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .idea-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .support-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            background: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .support-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .support-btn.supported {
            background: var(--primary-dark);
        }

        .idea-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 1rem;
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

    <!-- Filtreleme Bölümü -->
    <div class="filter-container animate-fade-in">
        <h2>Kategorilere Göre Filtrele</h2>
        <div class="filter-buttons">
            <a href="trending.php" class="filter-btn <?php echo $category === '' ? 'active' : ''; ?>">Tümü</a>
            <a href="trending.php?category=Teknoloji" class="filter-btn <?php echo $category === 'Teknoloji' ? 'active' : ''; ?>">Teknoloji</a>
            <a href="trending.php?category=İş" class="filter-btn <?php echo $category === 'İş' ? 'active' : ''; ?>">İş</a>
            <a href="trending.php?category=Sanat" class="filter-btn <?php echo $category === 'Sanat' ? 'active' : ''; ?>">Sanat</a>
        </div>
    </div>

    <!-- Fikirler Listesi -->
    <div class="idea-container">
        <div class="idea-list">
            <?php
            if (pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    ?>
                    <div class="idea-card animate-fade-in">
                        <div class="user-info">
                            <img src="../images/default-profile.jpg" alt="Profil Resmi">
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($row['role']); ?></div>
                            </div>
                        </div>
                        <span class="support-count"><?php echo $row['support_count']; ?> destek</span>
                        <h3 class="idea-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="idea-description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                        <span class="idea-category"><?php echo htmlspecialchars($row['category_name'] ?? 'Kategori Yok'); ?></span>

                        <?php if (!empty($row['media_url'])): ?>
                            <img src="<?php echo htmlspecialchars($row['media_url']); ?>" alt="Fikir görseli" class="idea-image">
                        <?php endif; ?>

                        <div class="idea-contact">
                            <?php if (!empty($row['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" class="contact-link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($row['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="contact-link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="idea-actions">
                            <button class="support-btn <?php echo $row['user_supported'] > 0 ? 'supported' : ''; ?>" 
                                    data-idea-id="<?php echo $row['id']; ?>">
                                <?php echo $row['user_supported'] > 0 ? '✓ Desteklendi' : '🤝 Destekle'; ?>
                            </button>
                        </div>

                        <div class="idea-date">Paylaşım Tarihi: <?php echo $row['created_at']; ?></div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="idea-card animate-fade-in"><p>Henüz hiç fikir paylaşılmamış.</p></div>';
            }
            ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Destekleme işlevselliği
        const supportButtons = document.querySelectorAll('.support-btn');
        
        supportButtons.forEach(button => {
            button.addEventListener('click', function() {
                const ideaId = this.dataset.ideaId;
                const supportCount = this.closest('.idea-card').querySelector('.support-count');
                
                fetch('support_idea.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'idea_id=' + ideaId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        supportCount.textContent = data.support_count + ' destek';
                        if (data.action === 'added') {
                            this.textContent = '✓ Desteklendi';
                            this.classList.add('supported');
                        } else {
                            this.textContent = '🤝 Destekle';
                            this.classList.remove('supported');
                        }
                    } else {
                        alert(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    console.error('Fetch hatası:', error);
                    alert('Bir hata oluştu: ' + error.message);
                });
            });
        });

        // Arama kutusu dışına tıklandığında sonuçları gizle
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !resultsBox.contains(event.target)) {
                resultsBox.classList.remove('visible');
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