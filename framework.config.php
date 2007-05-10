<?php
define('APP_DB_DRIVER','');
define('APP_DB_HOST','');
define('APP_DB_DATABASE','');
define('APP_DB_USER','');
define('APP_DB_PASS','');

define('DEVBLOCKS_REWRITE', false);
//define('DEVBLOCKS_LANGUAGE','en');
//define('DEVBLOCKS_THEME','default');
//define('DEVBLOCKS_DEBUG',true);

// Persistent Memory Caching (Cross-Thread)
//define('DEVBLOCKS_MEMCACHE_HOST','127.0.0.1');
//define('DEVBLOCKS_MEMCACHE_PORT','11211');

/****************************************************************************
 * [JAS]: Don't change the following unless you know what you're doing!
 ***************************************************************************/
define('APP_DEFAULT_CONTROLLER','core.controller.page');
define('APP_DB_PREFIX','cerb');
define('APP_PATH',realpath(dirname(__FILE__)));
define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');

// [TODO] Move to config
//define('DEVBLOCKS_VIRTUAL', serialize(array(
//    'forum.wgm.local' => '/forums/1/',
//    'mobile.wgm.local' => '/mobile/',
//    'helpdesk.wgm.local' => '/',
//)));

require_once(DEVBLOCKS_PATH . 'framework.defaults.php');

