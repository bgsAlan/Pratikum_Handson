FROM php:8.4-fpm
# Dependensi sistem + ekstensi PHP yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
git curl unzip libpng-dev libonig-dev libxml2-dev libzip-dev zip \
&& docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
# Pasang Composer dari image resminya (multi-stage copy, tanpa install manual)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
# Jalankan sebagai user non-root agar berkas yang dibuat container tidak dimiliki root
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www
USER www
EXPOSE 9000
CMD ["php-fpm"]