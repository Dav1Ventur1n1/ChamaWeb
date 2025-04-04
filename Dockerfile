FROM php:7.4-apache

# Instala o driver mysqli e pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia os arquivos do projeto para dentro do Apache
COPY . /var/www/html/

# Dá permissão aos arquivos
RUN chown -R www-data:www-data /var/www/html

# Ativa o mod_rewrite do Apache (se necessário)
RUN a2enmod rewrite
