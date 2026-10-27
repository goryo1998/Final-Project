# 1. Use official PHP image with Apache
FROM php:8.2-apache

# 2. Install MySQL extensions for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 3. Enable Apache rewrite module
RUN a2enmod rewrite

# 4. Copy your project files into Apache's web folder
COPY . /var/www/html/

# 5. Open port 80
EXPOSE 80
