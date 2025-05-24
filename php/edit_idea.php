<?php
session_start();
require_once 'db_connect.php';
require_once 'helpers/S3Helper.php'; // S3Helper dosyasını dahil et

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'error' => ''];

// Fikir ID'sini al
if (!isset($_GET['id'])) {
    header('Location: profile.php');
    exit();
}

$idea_id = $_GET['id'];

// Fikri ve ilişkili medyayı getir
$query = "SELECT i.*, m.id as media_id, m.s3_key, m.file_name, m.file_type FROM ideas i LEFT JOIN media m ON i.id = m.idea_id WHERE i.id = $1 AND i.user_id = $2";
$result = pg_query_params($conn, $query, array($idea_id, $user_id));

if (!$result || pg_num_rows($result) === 0) {
    header('Location: profile.php');
    exit();
}

$idea = pg_fetch_assoc($result);
$s3Helper = new S3Helper();

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $delete_media = isset($_POST['delete_media']) ? true : false; // Medyayı silme isteği

    // En az bir iletişim bilgisi gerekli
    if (empty($phone) && empty($email)) {
        $response['error'] = "Lütfen en az bir iletişim bilgisi (telefon veya e-posta) girin.";
    } else {
        $new_media_s3_key = $idea['s3_key'];
        $new_media_url = $idea['media_url'];
        $new_media_id = $idea['media_id'];

        // Mevcut medyayı silme isteği varsa
        if ($delete_media && !empty($idea['s3_key'])) {
            $deleteResult = $s3Helper->deleteFile($idea['s3_key']);
            if ($deleteResult['success']) {
                // Veritabanından medya kaydını sil
                $delete_media_db_query = "DELETE FROM media WHERE id = $1";
                pg_query_params($conn, $delete_media_db_query, array($idea['media_id']));

                $new_media_s3_key = null;
                $new_media_url = null;
                $new_media_id = null;
            } else {
                $response['error'] = "Mevcut medya silinirken bir hata oluştu: " . $deleteResult['error'];
            }
        }

        // Yeni medya yükleme işlemi
        if (empty($response['error']) && isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
            // Eğer mevcut medya varsa, önce onu S3 ve DB'den sil
            if (!empty($idea['s3_key'])) {
                 $deleteResult = $s3Helper->deleteFile($idea['s3_key']);
                 if ($deleteResult['success']) {
                     $delete_media_db_query = "DELETE FROM media WHERE id = $1";
                     pg_query_params($conn, $delete_media_db_query, array($idea['media_id']));
                 } else {
                     $response['error'] = "Mevcut medya silinirken bir hata oluştu: " . $deleteResult['error'];
                 }
            }

            if(empty($response['error'])){
                $uploadResult = $s3Helper->uploadFile($_FILES['media'], 'idea_media');

                if ($uploadResult['success']) {
                    $new_media_s3_key = $uploadResult['key'];
                    $new_media_url = $uploadResult['url'];
                    
                    // Yeni medya bilgisini media tablosuna kaydet
                    $insert_media_query = "INSERT INTO media (user_id, idea_id, file_name, file_type, file_size, s3_key) VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
                    $insert_media_result = pg_query_params($conn, $insert_media_query, array(
                        $user_id,
                        $idea_id, // Fikir ID'sini doğrudan kaydet
                        $_FILES['media']['name'],
                        $_FILES['media']['type'],
                        $_FILES['media']['size'],
                        $new_media_s3_key
                    ));

                     if ($insert_media_result) {
                        $new_media_id = pg_fetch_result($insert_media_result, 0, 'id');
                     } else {
                         $response['error'] = "Yeni medya bilgisi veritabanına kaydedilemedi.";
                         // Yüklenen dosyayı S3'ten sil
                         $s3Helper->deleteFile($new_media_s3_key);
                     }

                } else {
                    $response['error'] = "Yeni medya yüklenirken bir hata oluştu: " . $uploadResult['error'];
                }
            }
        }

        if (empty($response['error'])) {
            // Fikri güncelle
            $update_query = "UPDATE ideas SET 
                            title = $1, 
                            description = $2, 
                            category_id = $3, 
                            media_url = $4,
                            phone = $5,
                            email = $6,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE id = $7 AND user_id = $8";
            
            $update_result = pg_query_params($conn, $update_query, array(
                $title,
                $description,
                $category_id,
                $new_media_url, // Güncellenmiş medya URL'ini kaydet
                $phone,
                $email,
                $idea_id,
                $user_id
            ));

            if ($update_result) {
                header('Location: profile.php');
                exit();
            } else {
                $response['error'] = "Fikir güncellenirken bir veritabanı hatası oluştu.";
            }
        }
    }
}

// Kategori listesini al
$categories_query = "SELECT id, name FROM categories ORDER BY name";
$categories_result = pg_query($conn, $categories_query);
$categories = pg_fetch_all($categories_result);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fikir Düzenle - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Mevcut stiller */
        .edit-container {
            max-width: 800px;
            margin: 6rem auto 2rem;
            padding: 0 2rem;
        }

        .edit-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.5s ease;
        }

        .edit-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .edit-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .edit-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .edit-form {
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

        .edit-btn {
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

        .edit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .edit-btn svg {
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

        /* Medya yükleme ve önizleme stilleri */
        .media-upload-section {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .media-upload-section label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
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
            background: var(--background-color);
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

        .media-preview-container {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }

        .current-media-preview {
            position: relative;
            display: inline-block;
        }

        .current-media-preview img, .current-media-preview video {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
        }

        .delete-media-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .delete-media-btn:hover {
            background: #ef4444;
            transform: scale(1.1);
        }

        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-top: 1rem;
            box-shadow: var(--shadow-md);
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav>
        <div class="nav-left">
            <h1>FikirFon</h1>
        </div>
        <div class="nav-center">
            <input type="text" class="search-box" placeholder="Kullanıcı, fikir veya etiket ara...">
        </div>
        <div id="search-results" class="search-results"></div>
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
    </nav>

    <div class="edit-container">
        <div class="edit-card">
            <div class="edit-header">
                <h1>Fikir Düzenle</h1>
                <p>Fikrinizi güncelleyin</p>
            </div>

            <?php if (!empty($response['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($response['error']); ?>
                </div>
            <?php endif; ?>

            <form class="edit-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Başlık</label>
                    <input type="text" id="title" name="title" required placeholder="Fikrinizin başlığını girin" value="<?php echo htmlspecialchars($idea['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Açıklama</label>
                    <textarea id="description" name="description" required placeholder="Fikrinizi detaylı bir şekilde açıklayın"><?php echo htmlspecialchars($idea['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Kategori</label>
                    <select id="category" name="category" required>
                        <option value="">Kategori seçin</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ((int)$idea['category_id'] === (int)$category['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{10,11}" placeholder="5XX XXX XX XX" value="<?php echo htmlspecialchars($idea['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" placeholder="ornek@email.com" value="<?php echo htmlspecialchars($idea['email'] ?? ''); ?>">
                </div>

                <div class="media-upload-section">
                    <label>Mevcut Görsel/Belge</label>
                    <div class="media-preview-container">
                        <?php if (!empty($idea['media_url'])): ?>
                            <div class="current-media-preview">
                                <?php if (strpos($idea['file_type'], 'image') !== false): ?>
                                    <img src="<?php echo htmlspecialchars($idea['media_url']); ?>" alt="Mevcut medya">
                                <?php else: // Belge veya diğer dosya tipleri için link göster ?>
                                     <a href="<?php echo htmlspecialchars($idea['media_url']); ?>" target="_blank"><?php echo htmlspecialchars($idea['file_name']); ?></a>
                                <?php endif; ?>
                                <button type="button" class="delete-media-btn" onclick="deleteCurrentMedia(<?php echo $idea['media_id']; ?>)">&times;</button>
                            </div>
                        <?php else: ?>
                            <p>Mevcut medya yok.</p>
                        <?php endif; ?>
                    </div>

                    <label>Yeni Görsel/Belge Yükle</label>
                    <div class="file-upload">
                        <div class="file-upload-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div class="file-upload-text">
                            Yeni dosya yüklemek için tıklayın veya sürükleyin<br>
                            <strong>Resim veya Belge</strong> (max. 10MB)
                        </div>
                        <input type="file" name="media" accept="image/*, application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document" onchange="previewNewMedia(this)">
                    </div>
                    <img id="new-media-preview" class="preview-image" src="#" alt="Önizleme" style="display: none;">
                    <input type="hidden" name="delete_media" id="delete_media_flag" value="0">
                </div>

                <button type="submit" class="edit-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Değişiklikleri Kaydet
                </button>
            </form>
        </div>
    </div>

    <script>
    // Yeni medya önizlemesi
    function previewNewMedia(input) {
        const preview = document.getElementById('new-media-preview');
        const file = input.files && input.files[0];
        
        if (file) {
             // Sadece resim dosyalarını önizle
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                 // Resim olmayan dosyalar için önizlemeyi gizle
                 preview.style.display = 'none';
            }
             // Mevcut medya önizlemesini gizle
            const currentMediaContainer = document.querySelector('.media-preview-container');
            if(currentMediaContainer) {
                 currentMediaContainer.style.display = 'none';
            }

        } else {
            preview.style.display = 'none';
             // Mevcut medya önizlemesini tekrar göster
            const currentMediaContainer = document.querySelector('.media-preview-container');
            if(currentMediaContainer) {
                 currentMediaContainer.style.display = 'flex'; // veya block duruma göre
            }
        }
    }

    // Mevcut medyayı silme
    function deleteCurrentMedia(mediaId) {
        if (confirm('Mevcut medyayı silmek istediğinizden emin misiniz?')) {
            // Formdaki gizli alanı güncelleyerek silme isteğini belirt
            document.getElementById('delete_media_flag').value = 1;
            // Mevcut medya önizlemesini gizle
            const currentMediaContainer = document.querySelector('.media-preview-container');
            if(currentMediaContainer) {
                 currentMediaContainer.style.display = 'none';
            }
             // Yeni medya yükleme alanını göster
             document.querySelector('.file-upload').style.display = 'flex';
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
</body>
</html> 