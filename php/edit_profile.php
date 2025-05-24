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

// Kullanıcı bilgilerini ve profil resminin S3 keyini al
$query = "SELECT u.*, m.id as media_id, m.s3_key, m.file_name, m.file_type FROM users u LEFT JOIN media m ON u.id = m.user_id AND m.idea_id IS NULL WHERE u.id = $1";
$result = pg_query_params($conn, $query, array($user_id));
$user = pg_fetch_assoc($result);

$s3Helper = new S3Helper();

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $bio = trim($_POST['bio']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $delete_profile_image = isset($_POST['delete_profile_image']) ? (bool)$_POST['delete_profile_image'] : false; // Profil resmini silme isteği

    // Temel bilgileri güncelle
    $update_user_query = "UPDATE users SET first_name = $1, last_name = $2, bio = $3 WHERE id = $4";
    $update_user_result = pg_query_params($conn, $update_user_query, array($first_name, $last_name, $bio, $user_id));

    if ($update_user_result) {
        $profile_image_s3_key_to_delete = null;
        $profile_media_id_to_delete = null;

        // Mevcut profil resmi bilgileri
        $current_profile_image_s3_key = $user['s3_key'];
        $current_profile_media_id = $user['media_id'];

        // Mevcut profil resmini silme isteği varsa VEYA yeni resim yüklenecekse eskiyi sil
        if ($delete_profile_image || (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0 && !empty($current_profile_image_s3_key))) {
            if (!empty($current_profile_image_s3_key)) {
                $deleteResult = $s3Helper->deleteFile($current_profile_image_s3_key);
                if ($deleteResult['success']) {
                    // Veritabanından medya kaydını sil
                    $delete_media_db_query = "DELETE FROM media WHERE id = $1";
                    pg_query_params($conn, $delete_media_db_query, array($current_profile_media_id));

                    // Kullanıcının profile_image_url alanını temizle
                    $update_user_image_query = "UPDATE users SET profile_image_url = NULL WHERE id = $1";
                    pg_query_params($conn, $update_user_image_query, array($user_id));

                } else {
                    $response['error'] = "Mevcut profil resmi S3'ten silinirken bir hata oluştu: " . $deleteResult['error'];
                }
            }
        }

        // Yeni profil resmi yükleme işlemi
        if (empty($response['error']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $uploadResult = $s3Helper->uploadFile($_FILES['profile_image'], 'profile_images');

            if ($uploadResult['success']) {
                $new_profile_image_s3_key = $uploadResult['key'];
                $new_profile_image_url = $uploadResult['url'];
                
                // Yeni medya bilgisini media tablosuna kaydet (idea_id NULL olacak)
                $insert_media_query = "INSERT INTO media (user_id, file_name, file_type, file_size, s3_key) VALUES ($1, $2, $3, $4, $5) RETURNING id";
                $insert_media_result = pg_query_params($conn, $insert_media_query, array(
                    $user_id,
                    $_FILES['profile_image']['name'],
                    $_FILES['profile_image']['type'],
                    $_FILES['profile_image']['size'],
                    $new_profile_image_s3_key
                ));

                if ($insert_media_result) {
                    // Kullanıcının profile_image_url alanını güncelle
                    $update_user_image_query = "UPDATE users SET profile_image_url = $1 WHERE id = $2";
                    pg_query_params($conn, $update_user_image_query, array($new_profile_image_url, $user_id));

                } else {
                    $response['error'] = "Yeni profil resmi bilgisi veritabanına kaydedilemedi.";
                    // Yüklenen dosyayı S3'ten sil
                    $s3Helper->deleteFile($new_profile_image_s3_key);
                }

            } else {
                $response['error'] = "Yeni profil resmi yüklenirken bir hata oluştu: " . $uploadResult['error'];
            }
        }

        // Şifre değişikliği varsa
        if (empty($response['error']) && !empty($current_password) && !empty($new_password)) {
            // Mevcut şifreyi kontrol et
            $check_query = "SELECT password FROM users WHERE id = $1";
            $check_result = pg_query_params($conn, $check_query, array($user_id));
            $current_hash = pg_fetch_result($check_result, 0, 'password');

            if (password_verify($current_password, $current_hash)) {
                if ($new_password === $confirm_password) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET password = $1 WHERE id = $2";
                    $update_result = pg_query_params($conn, $update_query, array($new_hash, $user_id));

                    if (!$update_result) {
                        $response['error'] = "Şifre güncellenirken bir hata oluştu.";
                    }
                } else {
                    $response['error'] = "Yeni şifreler eşleşmiyor.";
                }
            } else {
                $response['error'] = "Mevcut şifre yanlış.";
            }
        }

        if (empty($response['error'])) {
            header('Location: profile.php');
            exit();
        }
    } else {
        $response['error'] = "Profil bilgileri güncellenirken bir hata oluştu.";
    }
}

// Kategori listesini al (profil düzenleme sayfasında kategoriye gerek yok)
// $categories_query = "SELECT id, name FROM categories ORDER BY name";
// $categories_result = pg_query($conn, $categories_query);
// $categories = pg_fetch_all($categories_result);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Düzenle - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .edit-profile-container {
            max-width: 800px;
            margin: 6rem auto 2rem;
            padding: 0 2rem;
        }

        .edit-profile-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.5s ease;
        }

        .edit-profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .edit-profile-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .edit-profile-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .edit-profile-form {
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

        .password-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .password-section h2 {
            color: var(--text-primary);
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .save-btn {
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

        .save-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .save-btn svg {
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

        .profile-image-upload-section {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .profile-image-upload-section label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .current-profile-image-preview {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .current-profile-image-preview img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--shadow-md);
        }

        .delete-profile-image-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .delete-profile-image-btn:hover {
            background: #ef4444;
            transform: scale(1.1);
        }

        .new-profile-image-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 1rem;
            display: none;
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

    <div class="edit-profile-container">
        <div class="edit-profile-card">
            <div class="edit-profile-header">
                <h1>Profil Düzenle</h1>
                <p>Profil bilgilerinizi güncelleyin</p>
            </div>

            <?php if (!empty($response['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($response['error']); ?>
                </div>
            <?php endif; ?>

            <form class="edit-profile-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="first_name">Ad</label>
                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user['first_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Soyad</label>
                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user['last_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="bio">Kendinizi Tanıtın</label>
                    <textarea id="bio" name="bio" placeholder="Kendinizi kısaca tanıtın..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="profile-image-upload-section">
                    <label>Profil Resmi</label>
                    <div id="profile-image-container">
                        <?php if (!empty($user['s3_key'])): // Kullanıcının media tablosunda bir kaydı var mı? ?>
                            <div class="current-profile-image-preview" id="current-profile-image-element">
                                <img src="<?php echo $s3Helper->getFileUrl($user['s3_key']); ?>" alt="Mevcut Profil Resmi">
                                <button type="button" class="delete-profile-image-btn" onclick="deleteProfileImage()">×</button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($user['s3_key'])): // Mevcut resim yoksa veya silindiyse yükleme alanını göster ?>
                            <div class="file-upload" id="profile-file-upload-element">
                                <div class="file-upload-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </div>
                                <div class="file-upload-text">
                                    Profil resmi yüklemek için tıklayın veya sürükleyin<br>
                                    <strong>PNG, JPG veya GIF</strong> (max. 10MB)
                                </div>
                                <input type="file" name="profile_image" accept="image/*" onchange="previewProfileImage(this)">
                            </div>
                        <?php endif; ?>
                        
                         <img id="new-profile-image-preview" class="new-profile-image-preview" src="#" alt="Yeni Profil Resmi Önizleme" style="display: none;">
                    </div>
                     <input type="hidden" name="delete_profile_image" id="delete_profile_image_flag" value="0">
                </div>

                <div class="password-section">
                    <h2>Şifre Değiştir</h2>
                    <div class="form-group">
                        <label for="current_password">Mevcut Şifre</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Yeni Şifre</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <button type="submit" class="save-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Değişiklikleri Kaydet
                </button>
            </form>
        </div>
    </div>

    <script>
    // Yeni profil resmi önizlemesi
    function previewProfileImage(input) {
        const preview = document.getElementById('new-profile-image-preview');
        const currentImageElement = document.getElementById('current-profile-image-element');
        const fileUploadElement = document.getElementById('profile-file-upload-element');
        const file = input.files && input.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                 // Mevcut resmi gizle
                if(currentImageElement) {
                     currentImageElement.style.display = 'none';
                }
                 // Yükleme alanını gizle
                 if(fileUploadElement) {
                      fileUploadElement.style.display = 'none';
                 }
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
             // Mevcut resim varsa onu göster, yoksa yükleme alanını göster
             if(currentImageElement) {
                  currentImageElement.style.display = 'inline-block';
                  if(fileUploadElement) fileUploadElement.style.display = 'none';
             } else {
                 if(fileUploadElement) fileUploadElement.style.display = 'flex';
             }
        }
    }

     // Mevcut profil resmini silme
     function deleteProfileImage() {
         if (confirm('Profil resminizi silmek istediğinizden emin misiniz?')) {
             document.getElementById('delete_profile_image_flag').value = 1;
              // Mevcut resmi gizle
             const currentImageElement = document.getElementById('current-profile-image-element');
             if(currentImageElement) {
                  currentImageElement.style.display = 'none';
             }
             // Yeni yükleme alanını göster
             const fileUploadElement = document.getElementById('profile-file-upload-element');
             if(fileUploadElement) {
                 fileUploadElement.style.display = 'flex';
             }
              // Yeni önizlemeyi gizle (silme butonuna basılırsa yeni yüklenen önizlemesi görünmemeli)
             document.getElementById('new-profile-image-preview').style.display = 'none';
         }
     }

    // Sayfa yüklendiğinde mevcut durumun kontrolü (Yeni yükleme alanı veya mevcut resim)
    document.addEventListener('DOMContentLoaded', function() {
         const currentImageElement = document.getElementById('current-profile-image-element');
         const fileUploadElement = document.getElementById('profile-file-upload-element');

         if(currentImageElement) {
              currentImageElement.style.display = 'inline-block';
              if(fileUploadElement) fileUploadElement.style.display = 'none';
         } else {
             if(fileUploadElement) fileUploadElement.style.display = 'flex';
         }
    });

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
                                ${user.first_name} ${user.last_name} (${user['role']})
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