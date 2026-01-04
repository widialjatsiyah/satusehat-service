# Panduan Setup SATUSEHAT Service di Hosting atau Server Ubuntu

## Daftar Isi
1. [Persyaratan Sistem](#persyaratan-sistem)
2. [Setup di Server Ubuntu Local](#setup-di-server-ubuntu-local)
3. [Setup di Hosting Shared](#setup-di-hosting-shared)
4. [Konfigurasi Database](#konfigurasi-database)
5. [Konfigurasi Environment](#konfigurasi-environment)
6. [Konfigurasi Queue Worker](#konfigurasi-queue-worker)
7. [Konfigurasi Web Server](#konfigurasi-web-server)
8. [Testing dan Verifikasi](#testing-dan-verifikasi)

## Persyaratan Sistem

### Minimum Requirements
- PHP 8.0 atau lebih tinggi
- Database (MySQL 5.7+, PostgreSQL 9.6+, atau SQLite 3.8.8+)
- Composer
- Web server (Apache/Nginx)
- Git (opsional, untuk clone repository)

### PHP Extensions yang Dibutuhkan
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- cURL
- GD (jika perlu manipulasi gambar)

## Setup di Server Ubuntu Local

### 1. Install Dependencies Sistem

```bash
# Update package list
sudo apt update

# Install PHP dan extensions
sudo apt install -y php8.1 php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath php8.1-json php8.1-ldap php8.1-gmp

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Git
sudo apt install -y git

# Install database (pilih salah satu)
# MySQL
sudo apt install -y mysql-server mysql-client
# Atau PostgreSQL
sudo apt install -y postgresql postgresql-contrib
```

### 2. Clone Repository dan Install Dependencies

```bash
# Clone repository
git clone https://github.com/your-username/service-satusehat.git
cd service-satusehat

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (jika ada assets frontend)
npm install
npm run production  # atau npm run dev untuk development
```

### 3. Setup Database

```bash
# Untuk MySQL
sudo mysql -u root -p
CREATE DATABASE satusehat_service;
CREATE USER 'satusehat_user'@'localhost' IDENTIFIED BY 'password_kuat';
GRANT ALL PRIVILEGES ON satusehat_service.* TO 'satusehat_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Untuk PostgreSQL
sudo -u postgres psql
CREATE DATABASE satusehat_service;
CREATE USER satusehat_user WITH ENCRYPTED PASSWORD 'password_kuat';
GRANT ALL PRIVILEGES ON DATABASE satusehat_service TO satusehat_user;
\q
```

### 4. Konfigurasi Environment

```bash
# Copy file environment
cp .env.example .env

# Generate app key
php artisan key:generate
```

Edit file `.env` sesuai konfigurasi Anda:
```
APP_NAME="SATUSEHAT Service"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=satusehat_service
DB_USERNAME=satusehat_user
DB_PASSWORD=password_kuat

# Queue configuration
QUEUE_CONNECTION=database

# SATUSEHAT credentials
CLIENTID_STG=your_client_id
CLIENTSECRET_STG=your_client_secret
ORGID_STG=your_org_id
```

### 5. Jalankan Migrasi

```bash
# Jalankan migrasi database
php artisan migrate --force

# Jika perlu seed data awal
php artisan db:seed
```

### 6. Setup Queue Worker dengan Supervisor

```bash
# Install supervisor
sudo apt install supervisor

# Buat konfigurasi supervisor
sudo nano /etc/supervisor/conf.d/satusehat-worker.conf
```

Tambahkan konfigurasi berikut:
```
[program:satusehat-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Perbarui path ke direktori project Anda, lalu restart supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start satusehat-worker:*
```

### 7. Setup Cron Job

```bash
# Tambahkan cron job untuk Laravel scheduler
crontab -e
```

Tambahkan baris berikut:
```
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Konfigurasi Web Server

#### Untuk Nginx:
```bash
sudo nano /etc/nginx/sites-available/satusehat
```

Tambahkan konfigurasi:
```
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Protect sensitive files
    location ~* \.env$ {
        deny all;
    }
}
```

Aktifkan situs:
```bash
sudo ln -s /etc/nginx/sites-available/satusehat /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Untuk Apache:
```bash
sudo a2enmod rewrite
sudo nano /etc/apache2/sites-available/satusehat.conf
```

Tambahkan konfigurasi:
```
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your/project/public

    <Directory /path/to/your/project/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/satusehat_error.log
    CustomLog ${APACHE_LOG_DIR}/satusehat_access.log combined
</VirtualHost>
```

Aktifkan situs:
```bash
sudo a2ensite satusehat
sudo systemctl reload apache2
```

## Setup di Hosting Shared

### 1. Upload File

1. Kompres file project kecuali folder `vendor`, `node_modules`, dan file `.env.local`
2. Upload ke direktori root hosting (biasanya `public_html` atau `www`)
3. Ekstrak file di server

### 2. Install Dependencies via SSH atau cPanel

Jika hosting mendukung SSH:
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate key
php artisan key:generate
```

Jika tidak mendukung SSH, Anda bisa:
- Install dependencies di lokal lalu upload folder `vendor`
- Gunakan script PHP untuk menjalankan composer (jika diizinkan)

### 3. Konfigurasi Database

1. Buat database baru di cPanel
2. Buat user database dan catat kredensialnya
3. Import struktur database dari hasil migrasi

### 4. Konfigurasi Environment

1. Ubah file `.env.example` menjadi `.env`
2. Sesuaikan konfigurasi database dan SATUSEHAT credentials
3. Generate APP_KEY via command line atau manual

### 5. Jalankan Migrasi

Jika hosting mendukung SSH:
```bash
php artisan migrate --force
```

Jika tidak, Anda bisa menjalankan SQL dari file migrasi secara manual di phpMyAdmin.

### 6. Konfigurasi Queue (Jika Tersedia)

Beberapa hosting shared menyediakan cron job. Jika tersedia, tambahkan:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Untuk menjalankan queue worker, beberapa hosting menyediakan fitur background process, atau Anda bisa:
- Menjalankan `php artisan queue:work` via SSH secara manual (akan berjalan sampai dihentikan)
- Menggunakan cron job untuk memeriksa dan menjalankan queue worker

## Konfigurasi Database

### MySQL
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username_database
DB_PASSWORD=password_database
```

### PostgreSQL
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database
DB_USERNAME=username_database
DB_PASSWORD=password_database
```

## Konfigurasi Environment

File `.env` harus berisi:
```
APP_NAME="SATUSEHAT Service"
APP_ENV=production
APP_KEY=  # Diisi oleh php artisan key:generate
APP_DEBUG=false
APP_URL=https://domain-anda.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username
DB_PASSWORD=password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# SATUSEHAT Credentials
CLIENTID_STG=your_client_id
CLIENTSECRET_STG=your_client_secret
ORGID_STG=your_org_id
```

## Konfigurasi Queue Worker

Queue worker sangat penting karena service ini menggunakan queue untuk mengirim data ke SATUSEHAT.

### Supervisor (Direkomendasikan untuk Ubuntu Server)
Lihat bagian setup Ubuntu di atas untuk konfigurasi supervisor.

### Cron Job (Untuk hosting yang tidak mendukung supervisor)
Tambahkan entri cron:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Lalu buat script untuk menjaga queue worker tetap berjalan:
```bash
#!/bin/bash
# save as check_queue.sh
PROCESS=$(pgrep -f "php artisan queue:work")
if [ -z "$PROCESS" ]; then
    cd /path-to-project
    nohup php artisan queue:work --sleep=3 --tries=3 > /dev/null 2>&1 &
fi
```

Jalankan script ini setiap menit via cron:
```
* * * * * /path/to/check_queue.sh
```

## Konfigurasi Web Server

### Nginx
Pastikan konfigurasi Nginx mengarah ke folder `public/` dan memiliki aturan rewrite untuk Laravel.

### Apache
Pastikan mod_rewrite diaktifkan dan file `.htaccess` ada di folder `public/`:
```
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## Testing dan Verifikasi

### 1. Test Akses Web
Akses URL utama service, pastikan halaman Laravel tampil dengan benar.

### 2. Test API Endpoint
Gunakan Postman atau curl untuk test endpoint:
```bash
curl -X POST https://your-domain.com/api/encounter \
  -H "X-Clinic-Code: YOUR_CODE" \
  -H "X-Clinic-Secret: YOUR_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"nik_pasien": "1234567890123456", "tanggal_kunjungan": "2024-01-01T00:00:00Z", "jenis_layanan": "101", "jenis_kunjungan": "1", "poli": "100001", "dokter": "1234567890"}'
```

### 3. Test Queue Worker
Tambahkan data ke queue dan pastikan worker memprosesnya:
```bash
# Check status queue
php artisan queue:status

# Check failed jobs
php artisan queue:failed
```

### 4. Test Log
Periksa log di `storage/logs/laravel.log` untuk memastikan tidak ada error kritis.

## Troubleshooting Umum

### 1. Permission Error
Pastikan folder berikut memiliki permission yang benar:
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 2. Queue Worker Tidak Berjalan
- Pastikan queue worker dijalankan
- Periksa apakah supervisor atau cron sudah dikonfigurasi dengan benar
- Cek log queue worker

### 3. Memory Limit
Jika ada error memory limit, tambahkan di konfigurasi PHP:
```
php_value memory_limit 256M
```

### 4. Timezone
Pastikan timezone di `.env` dan PHP sesuai:
```
APP_TIMEZONE=Asia/Jakarta
```