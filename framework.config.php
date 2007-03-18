<?php
define('APP_DB_DRIVER','mysql');
define('APP_DB_HOST','localhost');
define('APP_DB_DATABASE','cerb4');
define('APP_DB_USER','cerb4');
define('APP_DB_PASS','cerb4');

define('APP_DEFAULT_URI','tickets');
//define('DEVBLOCKS_LANGUAGE','en');
//define('DEVBLOCKS_THEME','default');
//define('DEVBLOCKS_REWRITE',true);
//define('DEVBLOCKS_DEBUG',true);

/****************************************************************************
 * [JAS]: Don't change the following unless you know what you're doing!
 ***************************************************************************/
define('APP_PATH',realpath(dirname(__FILE__)));
define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');

require_once(DEVBLOCKS_PATH . 'framework.defaults.php');

