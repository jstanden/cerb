<?php
define('UM_PATH',realpath(dirname(__FILE__)));
define('UM_PLUGIN_PATH',UM_PATH.'/plugins/');
define('UM_ATTACHMENT_SAVE_PATH',UM_PATH.'/tmp/');
define('UM_ATTACHMENT_ACCESS_PATH','http://localhost/cerb4/tmp/');
define('UM_DIRECTORY',basename(realpath(dirname(__FILE__))));
//define('UM_WEBPATH',''); // uncomment to override

define('UM_LANGUAGE','en');
define('UM_THEME','default');

// Set UM_DEBUG to 'true' to turn on debugging output and 'false' to turn it off
define('UM_DEBUG','true');

define('LANG_CHARSET_MAIL_CONTENT_TYPE','text/plain');
define('LANG_CHARSET_CODE','iso-8859-1');

define('UM_DB_DRIVER','mysql');
define('UM_DB_HOST','localhost');
define('UM_DB_DATABASE','cerb4');
define('UM_DB_USER','cerberus');
define('UM_DB_PASS','cerberus');

?>