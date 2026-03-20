# Use PHP CLI image
FROM php:8.2-cli

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
        libonig-dev \
        libzip-dev \
        zip \
        unzip \
        git \
        default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql
    
# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer