# Stage 1: Vendor
FROM composer:latest as vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# Stage 2: Production
FROM php:8.2-apache

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Configure Apache
RUN a2enmod rewrite
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf

# App setup
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor

# Optimization
RUN composer dump-autoload --optimize --no-dev

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Environment Variables for Cloud Run
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Cloud Run uses the $PORT environment variable
CMD ["apache2-foreground"]
