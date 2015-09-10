<?php
if(!defined('APP_DB_PCONNECT'))
	define('APP_DB_PCONNECT',false);

if(!defined('APP_DB_PREFIX'))
	define('APP_DB_PREFIX','');

if(!defined('APP_DB_ENGINE'))
	define('APP_DB_ENGINE','MyISAM');

if(!defined('APP_DB_DATABASE'))
	define('APP_DB_DATABASE','');

if(!defined('APP_DB_HOST'))
	define('APP_DB_HOST','');

if(!defined('APP_DB_USER'))
	define('APP_DB_USER','');

if(!defined('APP_DB_PASS'))
	define('APP_DB_PASS','');

if(!defined('APP_DB_SLAVE_HOST'))
	define('APP_DB_SLAVE_HOST','');

if(!defined('APP_DB_SLAVE_USER'))
	define('APP_DB_SLAVE_USER','');

if(!defined('APP_DB_SLAVE_PASS'))
	define('APP_DB_SLAVE_PASS','');

if(!defined('DEVBLOCKS_LANGUAGE'))
	define('DEVBLOCKS_LANGUAGE','en');

if(!defined('DEVBLOCKS_REWRITE'))
	define('DEVBLOCKS_REWRITE',false);

if(!defined('APP_DEFAULT_CONTROLLER'))
	define('APP_DEFAULT_CONTROLLER',''); // 404?

if(!defined('APP_PATH'))
	define('APP_PATH',dirname(__FILE__));

if(!defined('APP_STORAGE_PATH'))
	define('APP_STORAGE_PATH',APP_PATH . '/storage');

if(!defined('APP_TEMP_PATH'))
	define('APP_TEMP_PATH',APP_STORAGE_PATH . '/tmp');

if(!defined('DEVBLOCKS_PATH'))
	define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');

if(!defined('LANG_CHARSET_CODE'))
	define('LANG_CHARSET_CODE','utf-8');

if(!defined('DB_CHARSET_CODE'))
	define('DB_CHARSET_CODE','utf8');

if(!defined('APP_SESSION_NAME'))
	define('APP_SESSION_NAME', 'Devblocks');

if(!defined('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE'))
	define('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE', false);

if(!defined('ONDEMAND_MODE'))
	define('ONDEMAND_MODE', false);

if(!defined('DEVELOPMENT_MODE'))
	define('DEVELOPMENT_MODE', false);

if(!defined('DEVELOPMENT_MODE_QUERIES'))
	define('DEVELOPMENT_MODE_QUERIES', false);

if(!defined('DEVELOPMENT_MODE_ALLOW_CSRF'))
	define('DEVELOPMENT_MODE_ALLOW_CSRF', false);

// ----

@ini_set('session.gc_maxlifetime','86400');
@ini_set('session.save_path',APP_TEMP_PATH);
@date_default_timezone_set(date_default_timezone_get());
@ini_set('magic_quotes_gpc',0);
@set_magic_quotes_runtime(0);
@ignore_user_abort(true);