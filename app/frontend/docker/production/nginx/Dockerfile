FROM yiisoftware/yii2-php:8.1-fpm-nginx

LABEL org.opencontainers.image.source https://github.com/audetv/fct-search

COPY ./frontend/docker/production/nginx/conf.d /etc/nginx/conf.d

WORKDIR /app

COPY ./ ./

HEALTHCHECK --interval=5s --timeout=3s --start-period=1s CMD curl --fail http://127.0.0.1/health || exit 1
