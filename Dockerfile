FROM php:8.1-bullseye

LABEL org.opencontainers.image.authors="Ray Cardillo <ray.cardillo@couchbase.com>"

ENV container docker

RUN mkdir /app
WORKDIR /app

RUN apt-get update -y \
    && \
    apt-get install -y \
    git cmake build-essential \
    software-properties-common \
    libzip-dev zip \
    wget curl jq \
    ruby gnupg2  \
    sed vim \
    openssl libssl-dev

RUN git clone --recurse-submodules https://github.com/couchbaselabs/couchbase-php-client.git

WORKDIR /app/couchbase-php-client

# For testing PRs
#RUN git fetch origin pull/6/head \
#    && \
#    git checkout -b pullrequest FETCH_HEAD

ENV CB_PHP_PREFIX=/usr/local

RUN ./bin/build
RUN ./bin/package

RUN pecl install ./couchbase-4.0.0.tgz \
    && \
    docker-php-ext-enable couchbase

# THE REST IS FOR REFERENCE

# RUN composer install

# Clear config cache
# RUN php artisan config:clear

# Set the entrypoint
ENTRYPOINT ["/bin/bash", "-l", "-c"]
