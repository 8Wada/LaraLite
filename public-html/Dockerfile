FROM php:8.1-apache

ENV ACCEPT_EULA=Y
ENV DEBIAN_FRONTEND=noninteractive TZ=America/Chihuahua

# ================== Paquetes del SO ==================
RUN apt-get update && apt-get install -y --no-install-recommends \
    nano wget gnupg2 uuid-dev libuuid1 \
    libxml2-dev libssl-dev tzdata libcap2-bin \
 && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
 && echo $TZ > /etc/timezone \
 && dpkg-reconfigure -f noninteractive tzdata \
 && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*

# ================== Extensiones PHP ==================
RUN pecl install uuid && docker-php-ext-enable uuid
RUN docker-php-ext-install calendar pdo_mysql mysqli soap

# ================== Variables de entorno ==================
ENV SOURCE=pre-laralite \
    ENDPOINT=mysql-laralite \
    APP_NAME=laralite \
    MAIL_DRIVER=ucol \
    MAIL_HOST=https://dca2.ucol.mx/pre/v1/correo \
    DB_HOST=mysql-laralite \
    DB_DATABASE=laralite_db \
    DB_USERNAME=laralite_user \
    DB_PASSWORD=laralite_password

# ================== Configuración de directorios ==================
# Crear directorios necesarios ANTES de copiar
RUN mkdir -p /var/www/laralite/public_content \
    /var/www/private_content

# Copiar archivos al contenedor
## frontend
COPY front/ /var/www/laralite/front/
COPY laralite-api/ /var/www/laralite/laralite/
COPY simplesaml/  /var/www/simplesaml/


# ================== Configuración Apache ==================
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite && \
    echo "display_errors = Off" >> /usr/local/etc/php/php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/php.ini

# --- No-root en puerto 80 --- 
RUN setcap 'cap_net_bind_service=+ep' /usr/sbin/apache2

#Preparar permisos de runtime para www-data 
RUN mkdir -p /var/log/apache2 /var/run/apache2 /var/lock/apache2 /var/log/php \
 && chown -R www-data:www-data \
      /var/www/laralite \
      /var/www/simplesaml \
      /var/log/apache2 /var/run/apache2 /var/lock/apache2 /var/log/php \
 && chmod -R u+rwX,g+rwX \
      /var/www/laralite

#Ejecutar como usuario no-root 
USER www-data

WORKDIR /var/www/laralite
EXPOSE 80
CMD ["apache2-foreground"]
