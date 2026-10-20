FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction

COPY . .

EXPOSE 8000

# Upload limit to 2 Mo
CMD ["php", "-d", "display_errors=0", "-d", "upload_max_filesize=2M", "-d", "post_max_size=3M", "-S", "0.0.0.0:8000", "-t", "public"]
