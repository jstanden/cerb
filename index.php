<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

if(version_compare(PHP_VERSION, "7.2", "<")) {
	http_response_code(500);
	die("Cerb requires PHP 7.2 or later.");
}

if(version_compare(PHP_VERSION, "7.4", ">=")) {
	http_response_code(500);
	die("Cerb is currently incompatible with PHP 7.4 (use 7.2 or 7.3)");
}

if(!extension_loaded('mysqli')) {
	http_response_code(500);
	die("Cerb requires the 'mysqli' PHP extension.  Please enable it.");
}

require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

// If this is our first run, redirect to the installer
if('' == APP_DB_HOST
	|| '' == APP_DB_DATABASE
	|| DevblocksPlatform::isDatabaseEmpty()) {
		DevblocksPlatform::init();
		$url_writer = DevblocksPlatform::services()->url();
		$base_url = rtrim(preg_replace("/index\.php\/$/i",'',$url_writer->write('',true)),"/");
		header('Location: '.$base_url.'/install/index.php');
		exit;
	}

require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');

// Request

$request = DevblocksPlatform::readRequest();
DevblocksPlatform::setStateless(in_array(@$request->path[0], ['cron','portal','resource']));

if(DevblocksPlatform::isStateless()) {
	$_SESSION = [];
} else {
	DevblocksPlatform::services()->session();
}

// Do we need an update first?
if(!DevblocksPlatform::versionConsistencyCheck()) {
	if(0 != strcasecmp(@$request->path[0],"update")) {
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update','locked')));
		exit;
	}
}

// Localization

DevblocksPlatform::setDateTimeFormat(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::TIME_FORMAT, CerberusSettingsDefaults::TIME_FORMAT));

// Locale
DevblocksPlatform::setLocale((isset($_SESSION['locale']) && !empty($_SESSION['locale'])) ? $_SESSION['locale'] : 'en_US');

// Timezone
DevblocksPlatform::setTimezone();

// Time format
if(isset($_SESSION['time_format']))
	DevblocksPlatform::setDateTimeFormat($_SESSION['time_format']);

// Initialize Logging

$timeout = ini_get('max_execution_time');
$logger = DevblocksPlatform::services()->log();
$logger->info("[Devblocks] ** Platform starting (".date("r").") **");
$logger->info('[Devblocks] Time Limit: '. (($timeout) ? $timeout : 'unlimited') ." secs");
$logger->info('[Devblocks] Memory Limit: '. ini_get('memory_limit'));

// [JAS]: HTTP Request (App->Platform)
CerberusApplication::processRequest($request);

exit;