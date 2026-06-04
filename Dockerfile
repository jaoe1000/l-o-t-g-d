FROM php:8.4-apache

# Install database extensions and Git
RUN apt-get update && apt-get install -y git \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy your repository files (already checked out by GitHub Actions) into the web root
COPY . /var/www/html

# Clone the official upstream modules repository and inject them
RUN git clone https://github.com/NB-Core/modules.git /tmp/modules \
    && cp -r /tmp/modules/* /var/www/html/modules/ \
    && rm -rf /tmp/modules

# Set up the mandatory cache directory and web server permissions
RUN mkdir -p /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html
