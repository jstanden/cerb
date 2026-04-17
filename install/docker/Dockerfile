FROM ubuntu:24.04

ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV DEBIAN_FRONTEND=noninteractive

VOLUME /var/www/html/

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    gettext-base \
    php8.3-bcmath \
    php8.3-curl \
    php8.3-dom \
    php8.3-fpm \
    php8.3-gd \
    php8.3-gmp \
    php8.3-ldap \
    php8.3-mailparse \
    php8.3-mbstring \
    php8.3-memcache \
    php8.3-mysql \
    php8.3-opcache \
    php8.3-redis \
    php8.3-tidy \
    php8.3-xml \
    php8.3-yaml \
    php8.3-zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./_conf/www.conf /etc/php/8.3/fpm/pool.d/www.conf
COPY --chmod=0700 ./_conf/entry.sh /

ENTRYPOINT [ "/entry.sh" ]