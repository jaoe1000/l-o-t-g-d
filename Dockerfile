FROM php:8.4-apache

# Install system dependencies, database extensions, unzip, and Git
RUN apt-get update && apt-get install -y git unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# 1. Use the official production PHP configuration to hide layout warnings
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 2. Aggressively bump the server memory limit to 512MB
RUN echo "memory_limit = 512M" > "$PHP_INI_DIR/conf.d/memory-limit.ini"
#Set the server timezone so game days roll over at the correct local time
RUN echo "date.timezone = America/New_York" > "$PHP_INI_DIR/conf.d/timezone.ini"

# Copy the official Composer binary directly
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the active directory inside the container
WORKDIR /var/www/html

# Clone the official upstream modules repository and inject them FIRST
# Delete the toxic folder, and "flatten" the rest
# Copy the script first
COPY fix_modules.sh /tmp/fix_modules.sh

# Clone, run the fix script, and move in one layer to keep the build stable
RUN git clone https://github.com/NB-Core/modules.git /tmp/modules \
    && rm -rf /tmp/modules/_old_dragonprime_snapshot \
    && mkdir -p /var/www/html/modules \
    && chmod +x /tmp/fix_modules.sh \
    && /tmp/fix_modules.sh \
    && rm -rf /tmp/modules /tmp/fix_modules.sh

# Copy your local repository files OVER the modules (Last one wins)
COPY . /var/www/html

# 3. Search and destroy any hardcoded 64MB memory limits hidden in the legacy game code
RUN find . -type f -name "*.php" -exec sed -i '/ini_set.*memory_limit/Id' {} + \
    && find . -type f -name ".htaccess" -exec sed -i '/memory_limit/Id' {} + || true

# Run Composer cleanly inside the working directory
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader

# Set up cache, persistent config directory, account output, and symlink
RUN mkdir -p /var/www/html/cache /var/www/html/persistent_config /var/www/html/accounts-output \
    && ln -s /var/www/html/persistent_config/dbconnect.php /var/www/html/dbconnect.php \
    && chown -R www-data:www-data /var/www/html
