<?php
const APP_DB_HOST = '${CERB_DB_HOST}';
const APP_DB_USER = '${CERB_DB_USER}';
const APP_DB_PASS = '${CERB_DB_PASS}';
const APP_DB_DATABASE = '${CERB_DB_NAME}';

const APP_SERVICE_TOKEN = '${CERB_SERVICE_TOKEN}';
const APP_SERVICE_TOKEN_SCOPE = '${CERB_SERVICE_TOKEN_SCOPE}';

const DEVELOPMENT_MODE = true;

/****************************************************************************
 * [JAS]: Don't change the following unless you know what you're doing!
 ***************************************************************************/
define('APP_PATH',dirname(__FILE__));
const APP_STORAGE_PATH = APP_PATH . '/storage';
const APP_TEMP_PATH = APP_STORAGE_PATH . '/tmp';
const DEVBLOCKS_PATH = APP_PATH . '/libs/devblocks/';
const DEVBLOCKS_REWRITE = true;
const APP_DEFAULT_CONTROLLER = 'core.controller.page';

require_once(DEVBLOCKS_PATH . 'framework.defaults.php');