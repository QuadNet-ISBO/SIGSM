FROM php:8.3-apache-bookworm

COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libonig-dev libpng-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-sigsm.conf /etc/apache2/conf-available/sigsm.conf
RUN a2enconf sigsm
COPY . /var/www/html/SIGSM3/
RUN mkdir -p /var/www/html/SIGSM3/uploads/documentos \
    && chown -R www-data:www-data /var/www/html/SIGSM3/uploads \
    && find /var/www/html/SIGSM3/uploads -type d -exec chmod 750 {} \; \
    && find /var/www/html/SIGSM3/uploads -type f -exec chmod 640 {} \;
WORKDIR /var/www/html/SIGSM3
EXPOSE 80
