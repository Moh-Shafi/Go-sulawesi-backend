FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite

COPY apache-config.conf /etc/apache2/conf-enabled/000-custom.conf
COPY php-video.ini /usr/local/etc/php/conf.d/zz-video-uploads.ini
RUN a2enmod setenvif

EXPOSE 80
