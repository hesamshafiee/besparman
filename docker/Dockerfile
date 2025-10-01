FROM php:8.4-fpm

# Arguments from docker-compose
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    soap \
    ftp

# Install PHP extensions via PECL (redis, mongodb)
RUN pecl install redis mongodb \
    && docker-php-ext-enable redis mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a system user with the provided UID
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

# Use the non-root user
USER $user
