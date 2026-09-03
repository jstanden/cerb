#!/bin/bash
set -e

# Which pool definition this container runs. One pool per container, always.
CERB_FPM_POOL="${CERB_FPM_POOL:-www}"

chown www-data:www-data /mnt/storage
chmod 0770 /mnt/storage

# The config is already there: the `cerb-config` one-shot renders it and this container is gated on
# its success. Deployments that ship the code INSIDE the image write it per-container instead, with
# no sharing and no coordination -- either way, by this point it exists, so just check.
[ -s /var/www/html/framework.config.php ] || { echo "entry: framework.config.php is missing or empty" >&2; exit 1; }

# Install exactly one pool file, the `sites-available` idiom -- so a
# `docker exec <container> ls /etc/php/8.5/fpm/pool.d/` says which role it is running.
# envsubst is restricted to our own variables so FPM's `$pool` placeholder survives.
rm -f /etc/php/8.5/fpm/pool.d/*.conf
envsubst "\$CERB_WEB_CHILDREN \$CERB_BACKGROUND_CHILDREN" \
	< "/etc/php/8.5/fpm/pools-available/${CERB_FPM_POOL}.conf" \
	> "/etc/php/8.5/fpm/pool.d/${CERB_FPM_POOL}.conf"

exec php-fpm8.5 -F
