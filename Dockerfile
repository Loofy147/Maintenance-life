# Stage 1: Build stage with Composer
FROM composer:2 as builder

WORKDIR /app

# Copy application files
COPY . .

# Install dependencies without dev packages and optimize autoloader
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Final production stage
FROM php:8.2-fpm

# Set working directory
WORKDIR /app

# Install required system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libxml2-dev \
    --no-install-recommends \
    && docker-php-ext-install \
    pdo_sqlite \
    soap \
    mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy composer dependencies from the builder stage
COPY --from=builder /app/vendor /app/vendor

# Copy application code
COPY . .

# Set permissions for storage and logs
RUN mkdir -p /app/var/cache /app/var/logs /app/var/storage \
    && chown -R www-data:www-data /app/var \
    && chmod -R 775 /app/var

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]