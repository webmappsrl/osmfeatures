FROM php:8.2.15-fpm

ARG APP_ROOT
ARG COMMAND
ARG OPTIONS 

ENV APP_ROOT=${APP_ROOT}
ENV COMMAND=${COMMAND}
ENV OPTIONS=${OPTIONS}

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions pcntl

RUN mkdir -p "/etc/supervisor/logs"

COPY configs/supervisor/supervisord.conf /etc/supervisord.conf 

# Make sure supervisord is installed in the correct location
RUN which supervisord

ENTRYPOINT ["/usr/bin/supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]