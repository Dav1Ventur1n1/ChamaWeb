FROM php:7.4-apache

# Instalar dependências do sistema necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Instala o driver mysqli e pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia os arquivos do projeto para dentro do Apache
COPY . /var/www/html/

# Dá permissão aos arquivos
RUN chown -R www-data:www-data /var/www/html

# Ativa o mod_rewrite do Apache (se necessário)
RUN a2enmod rewrite

# Instalar as dependências do Composer (se houver um composer.json)
WORKDIR /var/www/html
RUN if [ -f "composer.json" ]; then composer install --no-interaction; fi