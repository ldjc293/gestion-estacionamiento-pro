FROM php:8.2-cli

# Instalar dependencias del sistema, librerías gráficas y controladores PostgreSQL (pdo_pgsql)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip \
    && apt-get clean && rm -rf /var/lib/apt-lists/*

# Instalar Composer ejecutable
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Composer (vlucas/phpdotenv, phpmailer, dompdf, etc.)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Puerto por defecto en Render
EXPOSE 10000
ENV PORT=10000

# Comando de inicio del servidor web PHP
CMD php -S 0.0.0.0:$PORT -t public public/index.php
