<?php
define('APP_DB_HOST','mysql');
define('APP_DB_USER','cerb');
define('APP_DB_PASS','s3cr3t');
define('APP_DB_DATABASE','cerb');

define('DEVELOPMENT_MODE', true);
define('AUTHORIZED_IPS_DEFAULTS', '172.16.');

/****************************************************************************
 * [JAS]: Don't change the following unless you know what you're doing!
 ***************************************************************************/
define('APP_DEFAULT_CONTROLLER','core.controller.page');
define('APP_DB_PREFIX','cerb');
define('APP_PATH',dirname(__FILE__));
define('APP_STORAGE_PATH',APP_PATH . '/storage');
define('APP_TEMP_PATH',APP_STORAGE_PATH . '/tmp');
define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');
define('DEVBLOCKS_REWRITE', true);

require_once(DEVBLOCKS_PATH . 'framework.defaults.php');