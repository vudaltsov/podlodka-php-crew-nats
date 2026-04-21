ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-cli-alpine

ARG UID=10001
ARG GID=10001

ENV LC_ALL=C.UTF-8

ENV COMPOSER_HOME=/home/dev/.composer
ENV PATH="${COMPOSER_HOME}/vendor/bin:${PATH}"

ARG XDEBUG_LOG=/home/dev/xdebug.log

RUN <<EOF
    set -eux

    apk add --no-cache \
        unzip

    (curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1') | sh -s \
        @composer \
        opcache \
        uv \
        pcntl \
        xdebug
EOF

RUN <<EOF
    set -eux

    ln -s /usr/local/bin/composer /usr/local/bin/c

    addgroup -g ${GID} dev
    adduser -u ${UID} -G dev -D dev

    chown -R dev:dev ${COMPOSER_HOME}

    touch ${XDEBUG_LOG}
    chown dev:dev ${XDEBUG_LOG}
    echo 'date.timezone = Europe/Moscow' >> /usr/local/etc/php/conf.d/date.ini
    echo 'xdebug.mode=off' >> /usr/local/etc/php/conf.d/xdebug.ini
    echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/xdebug.ini
    echo 'xdebug.log=${XDEBUG_LOG}' >> /usr/local/etc/php/conf.d/xdebug.ini
EOF

USER dev

ENV IN_CONTAINER=true
