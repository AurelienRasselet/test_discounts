FROM php:7.4-fpm
RUN apt-get update
RUN apt-get install -y openssl libssl-dev libcurl4-openssl-dev
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongo.ini