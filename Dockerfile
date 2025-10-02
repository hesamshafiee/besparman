ARG PHP_EXTENSIONS="apcu bcmath opcache pcntl pdo_mysql mongodb redis zip sockets imagick gd exif soap remoteip json curl pdo_sqlite sqlite3 ftp"
ARG APACHE_EXTENSIONS="remoteip"

FROM docker.arvancloud.ir/thecodingmachine/php:8.3-v5-apache AS php_base
ENV TZ=Asia/Tehran
ENV TEMPLATE_PHP_INI=development
ENV APACHE_EXTENSION_REMOTEIP=1
ENV PHP_EXTENSION_GD=1
ENV PHP_EXTENSION_CURL=1
ENV PHP_EXTENSION_BCMATH=1
ENV PHP_INI_MEMORY_LIMIT=8g
ENV PHP_INI_MAX_EXECUTION_TIME=300
ENV PHP_EXTENSION_PDO_SQLITE=1
ENV PHP_EXTENSION_SQLITE3=1
ENV PHP_INI_DATE__TIMEZONE='Asia/Tehran'
ENV PHP_EXTENSION_MONGODB=1
ENV PHP_EXTENSION_FTP=1

WORKDIR /var/www/html
COPY --chown=docker:docker ./docker/.env .
COPY --chown=docker:docker ./docker/.env.testing .
COPY --chown=docker:docker . .
# COPY --chown=docker:docker ./composer.json .
# COPY --chown=docker:docker ./composer.lock .
#RUN composer update
RUN composer install --optimize-autoloader --no-scripts

# RUN php artisan test

FROM php_base
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Copy the entrypoint script
# COPY --chown=docker:docker entrypoint.sh /usr/local/bin/entrypoint.sh

# # Set the entrypoint script
# ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
