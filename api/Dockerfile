FROM php:8.2-apache

# Instalar dependencias
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev git unzip zip libzip-dev && \
    docker-php-ext-install pdo pdo_pgsql zip

# Instalar Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Habilitar el módulo de reescritura de Apache (rewrite)
RUN a2enmod rewrite

# habilitar el módulo de encabezados para configurar CORS a nivel de Apache
RUN a2enmod headers

# Copia y habilita vhost que apunta a /public
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|DocumentRoot .*|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Instalar dependencias de PHP si existe composer.json
RUN if [ -f composer.json ]; then composer install --no-interaction --no-plugins --no-scripts; fi

# Asegurar permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
