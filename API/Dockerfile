FROM php:8.2-apache

# Install mysqli extension for MySQL connectivity
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all PHP API files
COPY . /var/www/html/

# Configure Apache to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
