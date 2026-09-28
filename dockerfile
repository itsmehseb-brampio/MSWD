FROM php:8.3-fpm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        mbstring \
        mysqli \
        pdo_mysql \
        opcache \
        zip \
    && rm -rf /var/lib/apt/lists/*

FROM php-base AS app-build

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --no-dev --optimize --no-scripts

FROM node:22-alpine AS assets-build

WORKDIR /app

COPY package.json ./

RUN npm install --no-audit --no-fund

COPY . .

RUN npm run build

FROM php-base AS app

WORKDIR /var/www/html

COPY --from=app-build /var/www/html ./

COPY --from=assets-build /app/public/build ./public/build

RUN mkdir -p public/uploads storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data public/uploads storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web

COPY nginx/mswd.conf /etc/nginx/conf.d/default.conf

COPY --from=app-build /var/www/html/public /var/www/html/public

COPY --from=assets-build /app/public/build /var/www/html/public/build