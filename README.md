     # FikirFon - Fikir Paylaşım Platformu

FikirFon, girişimciler ve yatırımcıları bir araya getiren, fikirlerin paylaşıldığı ve değerlendirildiği bir web platformudur. Kullanıcılar fikirlerini paylaşabilir, diğer kullanıcıların fikirlerini görebilir ve etkileşimde bulunabilir.

## Özellikler

- 👤 Kullanıcı Yönetimi (Kayıt, Giriş, Profil)
- 💡 Fikir Paylaşımı
- 📊 Kategori Sistemi
- 🔍 Kullanıcı Arama
- 📈 Trend Fikirler
- 📱 Responsive Tasarım

## Teknolojiler

- Backend: PHP 8.0
- Veritabanı: PostgreSQL
- Frontend: HTML5, CSS3, JavaScript
- Hosting: AWS RDS (Veritabanı)

## Kurulum

### Gereksinimler

- XAMPP (veya benzeri bir local server)
- PHP 8.0 veya üzeri
- PostgreSQL extension (PHP için)
- Git

### Adımlar

1. Projeyi klonlayın:
   ```bash
   git clone https://github.com/[kullanıcı-adı]/fikirfon.git
   cd fikirfon
   ```

2. XAMPP'i kurun ve başlatın:
   - [XAMPP İndirme Sayfası](https://www.apachefriends.org/download.html)
   - Apache ve PostgreSQL servislerini başlatın

3. PostgreSQL extension'ını aktif edin:
   - XAMPP Control Panel > Apache > Config > PHP (php.ini)
   - `;extension=pgsql` satırını bulun
   - Başındaki `;` işaretini kaldırın
   - Apache'yi yeniden başlatın

4. Veritabanı bağlantısını ayarlayın:
   - `php/db.php` dosyasını düzenleyin:
   ```php
   $host = "database-1.ckpmm2coabsv.us-east-1.rds.amazonaws.com";
   $port = "5432";
   $dbname = "fikirfon";
   $user = "postgres";
   $password = "MVY75MVY";
   ```

5. Projeyi XAMPP'in htdocs klasörüne kopyalayın:
   - Windows: `C:\xampp\htdocs\fikirfon`
   - Linux: `/opt/lampp/htdocs/fikirfon`
   - Mac: `/Applications/XAMPP/htdocs/fikirfon`

6. Tarayıcıdan projeyi açın:
   ```
   http://localhost/fikirfon
   ```

## Kullanım

1. Kayıt Ol:
   - "Kayıt Ol" butonuna tıklayın
   - Girişimci veya Yatırımcı rolünü seçin
   - Bilgilerinizi girin

2. Fikir Paylaş:
   - "Fikir Paylaş" butonuna tıklayın
   - Başlık ve açıklama girin
   - Kategori seçin
   - Paylaş butonuna tıklayın

3. Fikirleri Keşfet:
   - Ana sayfada tüm fikirleri görüntüleyin
   - Trend fikirler sayfasını ziyaret edin
   - Kullanıcı araması yapın

## Güvenlik

- SQL Injection koruması
- XSS koruması
- Güvenli şifre hashleme
- Oturum güvenliği




