FROM wordpress:latest

# Update and install required packages
RUN pecl install redis && \
    apt-get update && \
    apt-get install -y vim && \
    echo "extension=redis.so" >> /usr/local/etc/php/conf.d/docker-php-ext-bcmath.ini 