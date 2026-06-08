FROM php:8.2-apache

# Install PHP extensions yang dibutuhkan (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy Apache config untuk subfolder /libtrack
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Beri permission yang benar
RUN chown -R www-data:www-data /var/www/html
