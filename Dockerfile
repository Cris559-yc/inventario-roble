FROM php:7.4-apache
RUN docker-php-ext-install pdo_mysql
COPY index.php /var/www/html/index.php
EXPOSE 80
