<?php
define('APP_DB_DRIVER','');
define('APP_DB_HOST','');
define('APP_DB_DATABASE','');
define('APP_DB_USER','');
define('APP_DB_PASS','');
define('APP_DB_PCONNECT',false);

// Memcached (high-performance shared memory cache)
// optional, memcached 1.2.x + extension must be enabled
// list multiple servers comma-delimited: host1:port,host2:port
//define('DEVBLOCKS_CACHE_PREFIX',''); // ONLY A-Z, a-z, 0-9 and underscore
//define('DEVBLOCKS_MEMCACHED_SERVERS','127.0.0.1:11211');

define('APP_PARSER_ALLOW_IMPORTS', true);

/****************************************************************************
 * [JAS]: Don't change the following unless you know what you're doing!
 ***************************************************************************/
define('APP_DEFAULT_CONTROLLER','core.controller.page');
define('APP_DB_PREFIX','cerb');
define('APP_PATH',realpath(dirname(__FILE__)));
define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');
define('DEVBLOCKS_REWRITE', file_exists(dirname(__FILE__).'/.htaccess'));
define('DEMO_MODE',false);
define('AUTHORIZED_IPS_DEFAULTS', '');

require_once(DEVBLOCKS_PATH . 'framework.defaults.php');

