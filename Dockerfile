FROM php:8.2-apache

# Install necessary PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files into the Docker container
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/
EXPOSE 80
EXPOSE 80
