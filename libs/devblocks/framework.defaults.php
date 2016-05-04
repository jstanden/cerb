<?php
if(!defined('APP_DB_PCONNECT'))
	define('APP_DB_PCONNECT',false);

if(!defined('APP_DB_PREFIX'))
	define('APP_DB_PREFIX','');

if(!defined('APP_DB_ENGINE'))
	define('APP_DB_ENGINE','MyISAM');

if(!defined('APP_DB_ENGINE_FULLTEXT'))
	define('APP_DB_ENGINE_FULLTEXT', APP_DB_ENGINE);

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

if(!defined('APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS'))
	define('APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS', 5);

if(!defined('APP_DB_OPT_SLAVE_CONNECT_TIMEOUT_SECS'))
	define('APP_DB_OPT_SLAVE_CONNECT_TIMEOUT_SECS', 1);

if(!defined('APP_DB_OPT_READ_MASTER_AFTER_WRITE'))
	define('APP_DB_OPT_READ_MASTER_AFTER_WRITE', 0);

if(!defined('APP_DB_OPT_CONNECTION_RECONNECTS'))
	define('APP_DB_OPT_CONNECTION_RECONNECTS', 10);

if(!defined('APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS'))
	define('APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS', 1000);

if(!defined('DEVBLOCKS_LANGUAGE'))
	define('DEVBLOCKS_LANGUAGE','en');

if(!defined('DEVBLOCKS_REWRITE'))
	define('DEVBLOCKS_REWRITE',false);

if(!defined('DEVBLOCKS_CACHE_ENGINE'))
	define('DEVBLOCKS_CACHE_ENGINE', 'devblocks.cache.engine.disk');

if(!defined('DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE'))
	define('DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE', false);
	
if(!defined('DEVBLOCKS_CACHE_ENGINE_OPTIONS'))
	define('DEVBLOCKS_CACHE_ENGINE_OPTIONS', json_encode(array()));

if(!defined('DEVBLOCKS_HTTP_PROXY'))
	define('DEVBLOCKS_HTTP_PROXY', '');

if(!defined('DEVBLOCKS_SEARCH_ENGINE_PREVENT_CHANGE'))
	define('DEVBLOCKS_SEARCH_ENGINE_PREVENT_CHANGE', false);

if(!defined('DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE'))
	define('DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE', false);

if(!defined('CERB_FEATURES_PLUGIN_LIBRARY'))
	define('CERB_FEATURES_PLUGIN_LIBRARY', true);

if(!defined('CERB_LIMITS_MAILBOX_COUNT'))
	define('CERB_LIMITS_MAILBOX_COUNT', -1);

if(!defined('CERB_LIMITS_PORTAL_COUNT'))
	define('CERB_LIMITS_PORTAL_COUNT', -1);

if(!defined('APP_DEFAULT_CONTROLLER'))
	define('APP_DEFAULT_CONTROLLER',''); // 404?

if(!defined('APP_PATH'))
	define('APP_PATH',dirname(__FILE__));

if(!defined('APP_STORAGE_PATH'))
	define('APP_STORAGE_PATH',APP_PATH . '/storage');

if(!defined('APP_TEMP_PATH'))
	define('APP_TEMP_PATH',APP_STORAGE_PATH . '/tmp');

if(!defined('APP_SMARTY_COMPILE_PATH'))
	define('APP_SMARTY_COMPILE_PATH',APP_TEMP_PATH . '/templates_c');

if(!defined('APP_SMARTY_COMPILE_USE_SUBDIRS'))
	define('APP_SMARTY_COMPILE_USE_SUBDIRS', false);

if(!defined('APP_SMARTY_COMPILE_PATH_MULTI_TENANT'))
	define('APP_SMARTY_COMPILE_PATH_MULTI_TENANT', false);

if(!defined('DEVBLOCKS_PATH'))
	define('DEVBLOCKS_PATH',APP_PATH . '/libs/devblocks/');

if(!defined('LANG_CHARSET_CODE'))
	define('LANG_CHARSET_CODE','utf-8');

if(!defined('DB_CHARSET_CODE'))
	define('DB_CHARSET_CODE','utf8');

if(!defined('APP_SESSION_NAME'))
	define('APP_SESSION_NAME', 'Devblocks');

if(!defined('AUTHORIZED_IPS_DEFAULTS'))
	define('AUTHORIZED_IPS_DEFAULTS', '');

if(!defined('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE'))
	define('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE', false);

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
if(function_exists('set_magic_quotes_runtime'))
	@set_magic_quotes_runtime(0);
@ignore_user_abort(true);