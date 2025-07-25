# Use official PHP image with required extensions
FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set APP_KEY for production
ENV APP_KEY=base64:fYEawLnfD09Jh9oxSM9YzHmzE7+F76AqPeYs9R1VhDc=
ENV APP_DEBUG=true

# Expose port (will be set by Railway)
EXPOSE $PORT

# Make startup script executable
RUN chmod +x start.sh

# Start Laravel server
CMD ["./start.sh"]