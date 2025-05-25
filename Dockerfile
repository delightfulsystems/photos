FROM php:8.4-fpm

LABEL org.opencontainers.image.source https://github.com/delightfulsystems/photos

# Install Pixelfed prerequisites
RUN apt-get update -q && apt-get install -qy \
  libgd-dev \
  jpegoptim \
  optipng \
  pngquant \
  ffmpeg \
  libfreetype-dev \
  libjpeg62-turbo-dev \
  libpng-dev \
  libxpm-dev \
  libicu-dev \
  libzip-dev \
  libmbedtls-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install \
  bcmath \
  exif \
  iconv \
  intl \
  zip \
  pdo_mysql \
  mysqli pcntl

RUN pecl install redis-6.2.0 \
  && docker-php-ext-enable redis

# Use the production php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Allow overriding file upload and processing
RUN sed -i 's/post_max_size.*/post_max_size = ${PHP_POST_MAX_SIZE}/g' "$PHP_INI_DIR/php.ini"
RUN sed -i 's/upload_max_filesize.*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}/g' "$PHP_INI_DIR/php.ini"
RUN sed -i 's/max_execution_time.*/max_execution_time = ${PHP_MAX_EXECUTION_TIME}/g' "$PHP_INI_DIR/php.ini"

ENV PHP_POST_MAX_SIZE=256M
ENV PHP_UPLOAD_MAX_FILESIZE=256M
ENV PHP_MAX_EXECUTION_TIME=600

COPY --from=composer:2.8.8 /usr/bin/composer /usr/bin/composer

RUN curl -sL https://deb.nodesource.com/setup_23.x | bash \
   && apt-get install -y nodejs

COPY --chown=www-data:www-data . /var/www/html

RUN composer install --no-dev --no-interaction
RUN npm install

RUN php artisan event:cache \
  && php artisan route:cache \
  && php artisan view:cache
