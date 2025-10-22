# Mulai dari base image yang sudah memiliki Nginx dan PHP-FPM
# Contoh ini menggunakan image yang umum untuk lingkungan web PHP
FROM richarvey/nginx-php-fpm:2.2.0

# Salin semua file proyek Anda ke direktori kerja (biasanya /var/www/html)
COPY . /var/www/html

# --- Konfigurasi Image ---

# Tentukan web root (folder public Laravel)
ENV WEBROOT /var/www/html/public 

# Lewati instalasi Composer otomatis di base image jika sudah ada skrip deployment
ENV SKIP_COMPOSER 1 

# --- Konfigurasi Laravel ---

# Setel environment (penting untuk production)
ENV APP_ENV production
ENV APP_DEBUG false

# Arahkan log ke output standar Docker
ENV LOG_CHANNEL stderr

# Izinkan Composer berjalan sebagai root (diperlukan untuk build process)
ENV COMPOSER_ALLOW_SUPERUSER 1