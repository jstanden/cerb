<?php
define('DEVBLOCKS_PATH',realpath(dirname(__FILE__)));

ini_set('session.gc_maxlifetime','86400');
ini_set('session.save_path',DEVBLOCKS_PATH . '/tmp');

define('DEVBLOCKS_PLUGIN_PATH',DEVBLOCKS_PATH.'/plugins/');
define('DEVBLOCKS_ATTACHMENT_SAVE_PATH',DEVBLOCKS_PATH.'/tmp/');
define('DEVBLOCKS_ATTACHMENT_ACCESS_PATH','http://localhost/cerb4/tmp/');
define('DEVBLOCKS_DIRECTORY',basename(realpath(dirname(__FILE__))));
//define('DEVBLOCKS_WEBPATH',''); // uncomment to override

define('DEVBLOCKS_LANGUAGE','en');
define('DEVBLOCKS_THEME','default');

// Set DEVBLOCKS_DEBUG to 'true' to turn on debugging output and 'false' to turn it off
define('DEVBLOCKS_DEBUG','true');

define('LANG_CHARSET_MAIL_CONTENT_TYPE','text/plain');
define('LANG_CHARSET_CODE','iso-8859-1');

define('DEVBLOCKS_DB_DRIVER','mysql');
define('DEVBLOCKS_DB_HOST','localhost');
define('DEVBLOCKS_DB_DATABASE','cerb4');
define('DEVBLOCKS_DB_USER','cerberus');
define('DEVBLOCKS_DB_PASS','cerberus');

?>