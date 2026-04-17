#!/bin/bash
chown www-data:www-data /var/www/html/storage \
&& chmod 0770 /var/www/html/storage \
&& envsubst "\$CERB_DB_HOST \$CERB_DB_NAME \$CERB_DB_USER \$CERB_DB_PASS \$CERB_SERVICE_TOKEN \$CERB_SERVICE_TOKEN_SCOPE" \
  < /tmp/cerb.framework.config.template > /var/www/html/framework.config.php \
&& php-fpm8.3 -F