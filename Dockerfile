FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Copiamo tutto il progetto
COPY . /var/www/html/

# Permessi
RUN chown -R www-data:www-data /var/www/html

# Installiamo le dipendenze (PHPMailer)
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader