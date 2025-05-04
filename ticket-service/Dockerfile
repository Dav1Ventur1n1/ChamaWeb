FROM php:7.4-apache

# Copia os arquivos da aplicação para o Apache
COPY . /var/www/html/

# Habilita o mod_rewrite (opcional)
RUN a2enmod rewrite

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html

# Expõe a porta 80 (internamente)
EXPOSE 80
