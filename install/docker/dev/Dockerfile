FROM php:8.2-fpm-bookworm

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apt-get update && apt-get install -y --no-install-recommends \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libmemcached-dev \
      libpng-dev \
      libyaml-dev \
      libzip-dev

RUN docker-php-ext-install -j$(nproc) bcmath \
  && docker-php-ext-install -j$(nproc) mysqli \
  && docker-php-ext-install -j$(nproc) zip \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd \
  && docker-php-ext-install -j$(nproc) opcache \
  && pecl install mailparse \
  && docker-php-ext-enable mailparse \
  && pecl install memcache \
  && docker-php-ext-enable memcache \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && pecl install yaml \
  && docker-php-ext-enable yaml \
  && rm -rf /var/lib/apt/lists/*

COPY --chmod=0700 _conf/entry.sh /

ENTRYPOINT [ "/entry.sh" ]