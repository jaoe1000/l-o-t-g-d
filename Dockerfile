FROM php:8.4-apache

# Install system dependencies, database extensions, unzip, and Git
RUN apt-get update && apt-get install -y git unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Bump PHP memory limit to 256MB to give the game plenty of headroom
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Copy the official Composer binary directly from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy your repository files into the web root
COPY . /var/www/html

# Clone the official upstream modules repository and inject them
RUN git clone https://github.com/NB-Core/modules.git /tmp/modules \
    && cp -r /tmp/modules/* /var/www/html/modules/ \
    && rm -rf /tmp/modules

# Run Composer to install all required PHP dependencies automatically
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader

# Set up the mandatory cache directory and web server permissions
RUN mkdir -p /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html
