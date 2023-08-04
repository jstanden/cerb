FROM ubuntu:23.04

ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV DEBIAN_FRONTEND=noninteractive

VOLUME /var/www/html/

RUN apt-get update && apt-get install -y \
    php8.1-bcmath \
    php8.1-curl \
    php8.1-dom \
    php8.1-fpm \
    php8.1-gd \
    php8.1-gmp \
    php8.1-mailparse \
    php8.1-mbstring \
    php8.1-mysql \
    php8.1-opcache \
    php8.1-tidy \
    php8.1-xml \
    php8.1-yaml \
    php8.1-zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./_conf/www.conf /etc/php/8.1/fpm/pool.d/www.conf
COPY --chmod=0700 ./_conf/entry.sh /

ENTRYPOINT [ "/entry.sh" ]