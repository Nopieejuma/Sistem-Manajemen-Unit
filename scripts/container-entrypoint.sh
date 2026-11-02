#!/bin/sh
set -eu
umask 077

cd /app

php -r '
$key = getenv("APP_KEY") ?: "";
$decoded = str_starts_with($key, "base64:") ? base64_decode(substr($key, 7), true) : $key;
if (!is_string($decoded) || strlen($decoded) !== 32) {
    fwrite(STDERR, "A valid APP_KEY must be injected at runtime; no key was generated.\n");
    exit(1);
}
if (filter_var(getenv("APP_DEBUG"), FILTER_VALIDATE_BOOLEAN)) {
    fwrite(STDERR, "APP_DEBUG must be disabled in the container.\n");
    exit(1);
}
$url = parse_url(getenv("APP_URL") ?: "");
if (!is_array($url) || ($url["scheme"] ?? "") !== "https" || empty($url["host"])) {
    fwrite(STDERR, "APP_URL must be an absolute HTTPS URL supplied at runtime.\n");
    exit(1);
}
if (in_array(getenv("SESSION_DRIVER"), ["file", "array"], true)
    || in_array(getenv("CACHE_STORE"), ["file", "array", "null"], true)) {
    fwrite(STDERR, "Session and cache must survive container replacement.\n");
    exit(1);
}
'

mkdir -p /tmp/laravel/bootstrap/cache \
    /tmp/laravel/storage/framework/cache/data \
    /tmp/laravel/storage/framework/sessions \
    /tmp/laravel/storage/framework/views \
    /tmp/laravel/storage/logs \
    /tmp/laravel/storage/app/private \
    /tmp/laravel/storage/app/public \
    /tmp/caddy/config /tmp/caddy/data

for manifest in packages.php services.php; do
    if [ -f "/app/bootstrap/cache/$manifest" ]; then
        cp "/app/bootstrap/cache/$manifest" "/tmp/laravel/bootstrap/cache/$manifest"
    fi
done

php artisan config:cache --no-interaction
php artisan view:cache --no-interaction

if php -r 'exit(filter_var(getenv("WAREHOUSE_DIRECT_UPLOAD"), FILTER_VALIDATE_BOOLEAN) ? 0 : 1);'; then
    printf '%s\n' 'Direct photo uploads enabled: verify private/public bucket policies and configure the photo cleanup scheduler before production.' >&2
else
    printf '%s\n' 'WARNING: warehouse photos still use temporary local storage. Enable and configure direct object storage uploads before production.' >&2
fi

exec "$@"
