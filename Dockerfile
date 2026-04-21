ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-cli-alpine

ARG UID=10001
ARG GID=10001

ENV LC_ALL=C.UTF-8

RUN <<EOF
    set -eux

    (curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1') | sh -s \
        @composer \
        uv \
        pcntl

    addgroup -g ${GID} dev
    adduser -u ${UID} -G dev -D dev
EOF

USER dev

WORKDIR /messenger

COPY . ./

RUN --mount=type=cache,target=/home/dev/.composer/cache <<EOF
    set -eux
    composer install --no-dev --classmap-authoritative
EOF

ENV NATS='tcp://194.87.151.117:4222'

ENTRYPOINT ["php", "/messenger/src/chat.php"]
