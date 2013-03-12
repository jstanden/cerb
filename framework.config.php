<?php
define('APP_DB_DRIVER','mysqli');
define('APP_DB_ENGINE','myisam');
define('APP_DB_HOST','localhost');
define('APP_DB_DATABASE','cerb6dev');
define('APP_DB_USER','root');
define('APP_DB_PASS','%dadate%');
define('APP_DB_PCONNECT',false);

define('LANG_CHARSET_CODE','utf-8');
define('DB_CHARSET_CODE','utf8');

// Memcached (high-performance shared memory cache)
// optional, memcached 1.2.x + extension must be enabled
// list multiple servers comma-delimited: host1:port,host2:port
//define('DEVBLOCKS_CACHE_PREFIX',''); // ONLY A-Z, a-z, 0-9 and underscore
//define('DEVBLOCKS_MEMCACHED_SERVERS','127.0.0.1:11211');

//@ini_set('memory_limit', '64M');

/****************************************************************************
 * [JAS]: Don't change the following unless you know what you're doing!
 ***************************************************************************/
define('APP_DEFAULT_CONTROLLER','core.controller.page');
define('APP_DB_PREFIX','cerb');
define('APP_PATH',dirname(__FILE__));
define('APP_STORAGE_PATH',APP_PATH . '/storage');
define('APP_TEMP_PATH',APP_STORAGE_PATH . '/tmp');
define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');
define('DEVBLOCKS_REWRITE', file_exists(dirname(__FILE__).'/.htaccess'));
define('DEVELOPMENT_MODE', true);
define('SMARTY_DEBUG_MODE', false);
define('AUTHORIZED_IPS_DEFAULTS', '');
define('ONDEMAND_MODE', false);

require_once(DEVBLOCKS_PATH . 'framework.defaults.php');
