ARG IMAGE_USER=root
ARG IMAGE_USER_UID=1000
ARG IMAGE_USER_GID=1000
ARG APP_PATH=/project

FROM php:8.3.4-fpm-alpine AS base

ARG IMAGE_USER
ARG IMAGE_USER_UID
ARG IMAGE_USER_GID
ARG APP_PATH

RUN apk update \
    && apk add \
        curl \
        bash \
    && rm -rf /var/lib/apt/lists/* \
    # installing install-php-extensions
    && curl -sL -o install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod u+x ./install-php-extensions \
    # installing php extensions
    && ./install-php-extensions \
        intl \
        mysqli \
        opcache \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        sockets \
        zip \
        xdebug \
        pcov \
        rdkafka

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

WORKDIR $APP_PATH

USER $IMAGE_USER


# ###################
# prd_composer install composer and run composer install
# ###################
FROM base AS prd_composer

ARG IMAGE_USER
ARG IMAGE_USER_GID
ARG APP_PATH

USER root

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY app/ $APP_PATH

USER $IMAGE_USER

WORKDIR $APP_PATH

RUN apk update \
    && curl -sS https://getcomposer.org/installer >/tmp/composer_installer \
    && php -- --install-dir=/usr/local/bin --filename=composer </tmp/composer_installer \
    && rm -f /tmp/composer_installer

# running composer install
# --no-dev
RUN composer install --no-scripts --no-interaction --no-progress --prefer-dist --optimize-autoloader --ignore-platform-reqs


# ###################
# PROD
# ###################
FROM base AS prod

ARG IMAGE_USER
ARG IMAGE_USER_UID
ARG IMAGE_USER_GID
ARG APP_PATH

USER $IMAGE_USER

COPY app/ $APP_PATH
COPY app/.env $APP_PATH/.env
COPY docker/phpfpm/php.prd.ini /usr/local/etc/php/conf.d/99_php.ini

COPY --from=prd_composer $APP_PATH/vendor $APP_PATH/vendor

RUN mkdir -p $APP_PATH/var && chown -R $IMAGE_USER_UID:$IMAGE_USER_GID $APP_PATH