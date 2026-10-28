FROM composer:2 AS dependencies
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

FROM php:8.3-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf \
    && echo 'ServerSignature Off' >> /etc/apache2/apache2.conf
RUN printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/portfolio.conf \
    && a2enconf portfolio
WORKDIR /var/www/html
COPY . .
COPY --from=dependencies /build/vendor ./vendor
RUN chown -R www-data:www-data storage
