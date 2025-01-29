FROM registry.gitlab.com/ntb-entreprise-group/dodo-empire/dodo-shop/dodo-shop-api/ntbe-php:7.4
#FROM bitnami/laravel:7.4-fpm-alpine

# Install necessary packages
RUN apk add --no-cache \
    git \
    openssh \
    openssl \
    zip \
    unzip \
    zlib-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) zip pdo_mysql mysqli \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN apk add --no-cache --virtual .ssh-build-deps \
    build-base \
    openssl-dev \
    libressl-dev \
    ca-certificates \
    autoconf \
    automake \
    libtool \
    zlib-dev \
    git \
    && ssh-keygen -A \
    && ssh-agent | sed 's/^echo/#echo/' > /tmp/ssh-agent.sh \
    && chmod +x /tmp/ssh-agent.sh \
    && . /tmp/ssh-agent.sh \
    && ssh-add /root/.ssh/id_rsa \
    && apk del .ssh-build-deps
