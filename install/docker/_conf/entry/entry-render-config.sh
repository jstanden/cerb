#!/bin/bash
set -e

# Renders framework.config.php once, then exits. Run as a ONE-SHOT container that the PHP
# containers gate on (`depends_on: condition: service_completed_successfully`), which is why
# nothing here waits or retries -- if this fails, nothing starts.
#
# Two reasons it is its own container rather than a branch in entry.sh:
#
#   1. No race by construction. Every PHP container bind-mounts the SAME config file, so any
#      scheme where one of them writes it and the others wait is a convention holding back a
#      torn read. This removes the question instead of answering it.
#   2. The DB password and service token stay OUT of the PHP containers' environment. Only this
#      container is given them, so `docker inspect php-fpm` shows no credentials.
#
# This container exists ONLY because the demo bind-mounts ONE config file into several containers.
# Nothing else does that. On Cloud the code lives inside the image, so each container writes its own
# framework.config.php to its own filesystem -- nothing is shared, so there is nothing to coordinate.

: "${CERB_DB_HOST:?render-config: CERB_DB_HOST is required}"
: "${CERB_DB_NAME:?render-config: CERB_DB_NAME is required}"

envsubst "\$CERB_DB_HOST \$CERB_DB_NAME \$CERB_DB_USER \$CERB_DB_PASS \$CERB_SERVICE_TOKEN \$CERB_SERVICE_TOKEN_SCOPE" \
	< /tmp/cerb.framework.config.template > /tmp/cerb.framework.config.rendered

# Write IN PLACE, deliberately. framework.config.php is a single-FILE bind mount, so `mv` would
# replace the inode and every container that already opened the path would go on seeing the old
# file -- the usual write-temp-then-rename advice is WRONG for this mount shape.
cat /tmp/cerb.framework.config.rendered > /var/www/html/framework.config.php

echo "render-config: wrote /var/www/html/framework.config.php"
