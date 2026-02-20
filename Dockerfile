FROM php:8.4-cli AS builder
WORKDIR /build

RUN apt-get update && apt-get install -y \
    --no-install-recommends \
    unzip \
    git \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV APP_ENV=development

COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .
RUN php laracord app:build \
    && mkdir -p /out \
    && cp builds/laracord /out/laracord \
    && cp scripts/docker-entrypoint.sh /out/docker-entrypoint.sh

FROM php:8.4-cli-alpine AS runtime
WORKDIR /app
RUN apk add --no-cache \
    libpq \
    libzip \
    mysql-client \
    oniguruma \
    && apk add --no-cache --virtual .build-deps \
    autoconf \
    gcc \
    g++ \
    linux-headers \
    make \
    oniguruma-dev \
    openssl-dev \
    postgresql-dev \
    libzip-dev \
    zlib-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring sockets zip \
    && apk del .build-deps

COPY --from=builder /out/laracord /app/laracord
COPY --from=builder /out/docker-entrypoint.sh /app/docker-entrypoint.sh
RUN chmod +x /app/laracord /app/docker-entrypoint.sh

EXPOSE 8080

CMD ["/app/docker-entrypoint.sh"]