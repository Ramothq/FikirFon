<?php
session_start();
require_once 'db_connect.php';
require_once 'helpers/S3Helper.php'; // S3Helper dosyasını dahil et

// Hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'error' => '', 'debug' => []];

// AJAX isteği kontrolü
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Çıktı tamponlamayı başlat
    ob_start();

    header('Content-Type: application/json');
    
    // POST metodu kontrolü
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        $response['error'] = 'Geçersiz istek metodu';
        ob_clean(); // Tamponu temizle
        echo json_encode($response);
        exit();
    }

    // POST verilerini kontrol et
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['category'])) {
        $response['error'] = "Lütfen tüm zorunlu alanları doldurun.";
        ob_clean(); // Tamponu temizle
        echo json_encode($response);
        exit();
    }

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_name = trim($_POST['category']); // Formdan gelen kategori adı
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // En az bir iletişim bilgisi gerekli
    if (empty($phone) && empty($email)) {
        $response['error'] = "Lütfen en az bir iletişim bilgisi (telefon veya e-posta) girin.";
        ob_clean(); // Tamponu temizle
        echo json_encode($response);
        exit();
    }

    // Kategori adından ID'yi bul
    $category_query = "SELECT id FROM categories WHERE name = $1";
    $category_result = pg_query_params($conn, $category_query, array($category_name));

    if (!$category_result || pg_num_rows($category_result) == 0) {
        $response['error'] = "Belirtilen kategori bulunamadı.";
        $response['debug'][] = "Category Name: " . $category_name;
        $response['debug'][] = pg_last_error($conn);
        ob_clean(); // Tamponu temizle
        echo json_encode($response);
        exit();
    }

    $category_id = pg_fetch_result($category_result, 0, 'id');

    $media_url = null;

    // Medya yükleme işlemi
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = time() . "_" . basename($_FILES["media"]["name"]);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_path)) {
            $media_url = "uploads/" . $filename;
        } else {
            $response['error'] = "Medya yüklenirken bir hata oluştu.";
            ob_clean(); // Tamponu temizle
            echo json_encode($response);
            exit();
        }
    }

    // Fikri veritabanına kaydet
    $insert_query = "INSERT INTO ideas (user_id, title, description, category_id, phone, email, media_url) 
                     VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING id";
    
    $insert_result = pg_query_params($conn, $insert_query, array(
        $user_id,
        $title,
        $description,
        $category_id, // Kategori ID'sini kullan
        $phone,
        $email,
        $media_url
    ));

    if ($insert_result) {
        $response['success'] = true;
        $response['message'] = "Fikriniz başarıyla paylaşıldı!";
    } else {
        $response['error'] = "Fikir kaydedilirken bir hata oluştu.";
        $response['debug'][] = pg_last_error($conn);
    }

    ob_clean(); // Tamponu temizle
    echo json_encode($response);
    exit();
}

// Normal sayfa yüklemesi için HTML içeriği
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fikir Paylaş - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .share-container {
            max-width: 800px;
            margin: 6rem auto 2rem;
            padding: 0 2rem;
        }

        .share-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.5s ease;
        }

        .share-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .share-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .share-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .share-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-group select {
            padding: 0.8rem 1rem;
            border: 2px solid var(--primary-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-primary);
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.2);
        }

        .share-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .share-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .share-btn svg {
            width: 20px;
            height: 20px;
        }

        .error-message {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease;
        }

        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            border: 2px dashed var(--primary-light);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload:hover {
            border-color: var(--primary-color);
            background: rgba(107, 70, 193, 0.05);
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .file-upload-text {
            color: var(--text-secondary);
            text-align: center;
        }

        .file-upload-text strong {
            color: var(--primary-color);
        }

        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-top: 1rem;
            display: none;
        }

        .preview-image.visible {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .success-message, .error-message {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .error-message {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
        }

        .success-message svg, .error-message svg {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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

    <div class="share-container">
        <div class="share-card">
            <div class="share-header">
                <h1>Fikir Paylaş</h1>
                <p>Fikrinizi toplulukla paylaşın ve geri bildirim alın</p>
            </div>

            <div id="success-message" class="success-message" style="display: none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>Fikriniz başarıyla paylaşıldı!</span>
            </div>

            <div id="error-message" class="error-message" style="display: none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span></span>
            </div>

            <form class="share-form" id="shareForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Başlık</label>
                    <input type="text" id="title" name="title" required placeholder="Fikrinizin başlığını girin" value="<?php echo htmlspecialchars($title ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Açıklama</label>
                    <textarea id="description" name="description" required placeholder="Fikrinizi detaylı bir şekilde açıklayın"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Kategori</label>
                    <select id="category" name="category" required>
                        <option value="">Kategori seçin</option>
                        <option value="Teknoloji" <?php echo (isset($category_name) && $category_name === 'Teknoloji') ? 'selected' : ''; ?>>Teknoloji</option>
                        <option value="İş" <?php echo (isset($category_name) && $category_name === 'İş') ? 'selected' : ''; ?>>İş</option>
                        <option value="Sanat" <?php echo (isset($category_name) && $category_name === 'Sanat') ? 'selected' : ''; ?>>Sanat</option>
                        <option value="Eğitim" <?php echo (isset($category_name) && $category_name === 'Eğitim') ? 'selected' : ''; ?>>Eğitim</option>
                        <option value="Sağlık" <?php echo (isset($category_name) && $category_name === 'Sağlık') ? 'selected' : ''; ?>>Sağlık</option>
                        <option value="Diğer" <?php echo (isset($category_name) && $category_name === 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Telefon (İsteğe bağlı)</label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{10,11}" placeholder="5XX XXX XX XX" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">E-posta (İsteğe bağlı)</label>
                    <input type="email" id="email" name="email" placeholder="ornek@email.com" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Görsel Ekle (İsteğe Bağlı)</label>
                    <div class="file-upload">
                        <div class="file-upload-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div class="file-upload-text">
                            Görsel yüklemek için tıklayın veya sürükleyin<br>
                            <strong>PNG, JPG veya GIF</strong> (max. 5MB)
                        </div>
                        <input type="file" name="media" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <img id="preview" class="preview-image" src="#" alt="Önizleme">
                </div>

                <button type="submit" class="share-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Fikri Paylaş
                </button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('shareForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('share_idea.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = 'profile.php';
            } else {
                alert(data.error || 'Bir hata oluştu. Lütfen tekrar deneyin.');
                console.error('Hata detayları:', data.debug);
            }
        })
        .catch(error => {
            console.error('Fetch hatası:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    });

    function previewImage(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.add('visible');
            }
            reader.readAsDataURL(input.files[0]);
        }
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
    </script>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; <?php echo date('Y'); ?> FikirFon. Tüm Hakları Saklıdır.</p>
    </div>
</footer>

</body>
</html>
