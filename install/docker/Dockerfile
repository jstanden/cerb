FROM ubuntu:26.04

ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV DEBIAN_FRONTEND=noninteractive

VOLUME /var/www/html/

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    gettext-base \
    php8.5-bcmath \
    php8.5-curl \
    php8.5-dom \
    php8.5-fpm \
    php8.5-gd \
    php8.5-gmp \
    php8.5-ldap \
    php8.5-mailparse \
    php8.5-mbstring \
    php8.5-memcache \
    php8.5-mysql \
    php8.5-redis \
    php8.5-tidy \
    php8.5-xml \
    php8.5-yaml \
    php8.5-zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./_conf/www.conf /etc/php/8.5/fpm/pool.d/www.conf
COPY --chmod=0700 ./_conf/entry.sh /

ENTRYPOINT [ "/entry.sh" ]