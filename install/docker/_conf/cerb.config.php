<?php
const APP_DB_HOST = 'mysql';
const APP_DB_USER = 'cerb';
const APP_DB_PASS = 's3cr3t';
const APP_DB_DATABASE = 'cerb';

const APP_SERVICE_TOKEN = 'sk_docker_token';
const APP_SERVICE_TOKEN_SCOPE = 'cron update';

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