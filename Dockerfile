# Utilizamos una imagen base de PHP con FPM
FROM php:8.3-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    build-essential \
    libpng-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libxpm-dev \
    libfreetype6-dev \
    locales \
    jpegoptim optipng pngquant gifsicle \
    nano \
    zip \
    libzip-dev \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip exif pcntl gd

# Instalar composer
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de composer y instalar dependencias
COPY composer.lock composer.json ./

# Añadir usuario para la aplicación Laravel
RUN groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

# Copiar el código de la aplicación antes de instalar dependencias
COPY . .

# Cambiar permisos de los archivos y directorios necesarios
RUN chown -R www:www /var/www && chmod -R 775 /var/www \
    && mkdir -p /var/log/supervisor && chown -R www:www /var/log/supervisor && chmod -R 775 /var/log/supervisor

# Cambiar el usuario actual a www antes de instalar dependencias
USER www

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Cambiar el usuario a root para iniciar servicios
USER root

# Exponer puertos
EXPOSE 80
EXPOSE 443

# Comando para iniciar supervisord que maneja Nginx y PHP-FPM
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

