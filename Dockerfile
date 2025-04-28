FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    gnupg \
    procps

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip pcntl
RUN docker-php-ext-configure pcntl --enable-pcntl

# Install and configure Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log=/var/log/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log_level=7" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.discover_client_host=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=VSCODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Set working directory
WORKDIR /var/www

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create an entrypoint script to handle user creation
RUN echo '#!/bin/bash\n\
# Create user with same UID as host if provided\n\
echo "Creating user with UID:$USER_ID and GID:$GROUP_ID"\n\
groupadd -g $GROUP_ID appgroup\n\
useradd -u $USER_ID -g $GROUP_ID -m -s /bin/bash appuser\n\
chown -R $USER_ID:$GROUP_ID /var/www\n\
# Run PHP-FPM as the new user\n\
sed -i "s/user = www-data/user = appuser/g" /usr/local/etc/php-fpm.d/www.conf\n\
sed -i "s/group = www-data/group = appgroup/g" /usr/local/etc/php-fpm.d/www.conf\n\
# Configure Git to trust the mounted directory\n\
git config --global --add safe.directory /var/www\n\
\n\
# Make storage directory writable\n\
chmod -R 755 /var/www/storage\n\
\n\
# Start PHP-FPM\n\
exec "$@"\n' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Set proper permissions for storage and cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/vendor || true
RUN mkdir -p /var/log/ \
&& touch /var/log/xdebug.log \
&& chown -R $USER_ID:$GROUP_ID /var/log/xdebug.log \
    && chmod -R 775 /var/log/xdebug.log

# Expose port 9000
EXPOSE 9000

# Use the entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]