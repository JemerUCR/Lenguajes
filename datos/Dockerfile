# Jalar la imagen base
FROM php:7.4-apache

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Instalar extensiones necesarias de PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', '496ffab3e8e8c5f67b3e58fe96b35f8a0489bc1535e5c52b60b3d8b8b4edb1288c6e8d0d3e714120d5be66b5e7fbcccb') === '496ffab3e8e8c5f67b3e58fe96b35f8a0489bc1535e5c52b60b3d8b8b4edb1288c6e8d0d3e714120d5be66b5e7fbcccb') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer

# Copiar archivos necesarios al contenedor
COPY ./composer.json /var/www/html/
COPY ./src /var/www/html/src
COPY ./public /var/www/html/public

# Instalar dependencias de Composer
RUN composer install

# Optimizar autoload de Composer
RUN composer dump-autoload

# Configurar el DocumentRoot de Apache
RUN sed -i 's/DocumentRoot.*$/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-enabled/000-default.conf

# Exponer el puerto 80
EXPOSE 80
