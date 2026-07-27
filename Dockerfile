FROM php:8.2-cli

# Instalar dependencias del sistema y controladores PostgreSQL (pdo_pgsql)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt-lists/*

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Puerto por defecto en Render
EXPOSE 10000
ENV PORT=10000

# Comando de inicio del servidor web PHP
CMD php -S 0.0.0.0:$PORT -t public public/index.php
