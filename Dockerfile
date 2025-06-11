FROM php:7.4-apache

# Apache servirá arquivos a partir do diretório padrão
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Instalar dependências do sistema necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    openssl \
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
RUN a2enmod rewrite ssl && \
    a2ensite default-ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
      -keyout /etc/ssl/private/selfsigned.key \
      -out /etc/ssl/certs/selfsigned.crt \
      -subj "/CN=localhost" && \
    sed -i 's#/etc/ssl/certs/ssl-cert-snakeoil.pem#/etc/ssl/certs/selfsigned.crt#' /etc/apache2/sites-available/default-ssl.conf && \
    sed -i 's#/etc/ssl/private/ssl-cert-snakeoil.key#/etc/ssl/private/selfsigned.key#' /etc/apache2/sites-available/default-ssl.conf

# Instalar as dependências do Composer (se houver um composer.json)
WORKDIR /var/www/html
RUN if [ -f "composer.json" ]; then composer install --no-interaction; fi

# Define o diretório de trabalho padrão
WORKDIR /var/www/html

EXPOSE 80 443
