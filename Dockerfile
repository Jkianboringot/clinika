# ---------- Stage 1: build ----------
FROM dunglas/frankenphp:1-php8.4 AS build

# PHP extensions Laravel + MySQL need
RUN install-php-extensions \
    pdo_mysql mysqli intl zip gd bcmath opcache pcntl exif

# Node 22, copied from the official image (Wayfinder needs PHP present
# during the Vite build, so Node has to live in the PHP image)
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
 && npm install -g pnpm@10

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# PHP deps first, so Docker can cache this layer
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev --no-interaction --no-progress \
      --prefer-dist --no-scripts --no-autoloader

# JS deps (pnpm, not npm — this project uses pnpm-lock.yaml)
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN pnpm install --frozen-lockfile

# Now the source
COPY . .

# Dummy key so artisan can boot during the build; the real one comes
# from Render's env vars at runtime
ENV APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=

RUN composer dump-autoload --optimize --no-dev \
 && php artisan package:discover --ansi \
 && pnpm run build \
 && rm -rf node_modules

# ---------- Stage 2: runtime ----------
FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
    pdo_mysql mysqli intl zip gd bcmath opcache pcntl exif

# Render's sandboxed runtime doesn't support file capabilities. The
# frankenphp binary ships with cap_net_bind_service set (so it can bind
# ports <1024 without root); exec-ing it there fails with "Operation not
# permitted". We bind to 8080 (unprivileged), so we don't need the
# capability — strip it.
RUN apt-get update && apt-get install -y --no-install-recommends libcap2-bin \
 && setcap -r "$(command -v frankenphp)" \
 && apt-get purge -y libcap2-bin && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# Sensible production PHP settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app
COPY --from=build /app /app

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]