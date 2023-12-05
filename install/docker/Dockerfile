FROM ubuntu:23.10

ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV DEBIAN_FRONTEND=noninteractive

VOLUME /var/www/html/

RUN apt-get update && apt-get install -y \
    php8.2-bcmath \
    php8.2-curl \
    php8.2-dom \
    php8.2-fpm \
    php8.2-gd \
    php8.2-gmp \
    php8.2-ldap \
    php8.2-mailparse \
    php8.2-mbstring \
    php8.2-mysql \
    php8.2-opcache \
    php8.2-tidy \
    php8.2-xml \
    php8.2-yaml \
    php8.2-zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./_conf/www.conf /etc/php/8.2/fpm/pool.d/www.conf
COPY --chmod=0700 ./_conf/entry.sh /

ENTRYPOINT [ "/entry.sh" ]