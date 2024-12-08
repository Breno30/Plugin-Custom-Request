FROM wordpress:latest

# Update and install required packages
RUN pecl install redis && \
    apt-get update && \
    apt-get install -y vim && \
    apt-get install -y redis-tools && \
    echo "extension=redis.so" >> /usr/local/etc/php/conf.d/docker-php-ext-bcmath.ini 