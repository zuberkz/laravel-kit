# PHP для проверок пакета: образ composer + intl, без которого не ставится Filament
FROM composer:2

RUN apk add --no-cache icu-dev && docker-php-ext-install intl
