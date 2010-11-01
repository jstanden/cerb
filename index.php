<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

if(version_compare(PHP_VERSION, "5.2", "<"))
	die("Cerberus Helpdesk 5.x requires PHP 5.2 or later.");

require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

// If this is our first run, redirect to the installer
if('' == APP_DB_DRIVER 
	|| '' == APP_DB_HOST 
	|| '' == APP_DB_DATABASE 
	|| null == ($db = DevblocksPlatform::getDatabaseService())
	|| DevblocksPlatform::isDatabaseEmpty()) {
		DevblocksPlatform::init();
		$url_writer = DevblocksPlatform::getUrlService();
		$base_url = rtrim(preg_replace("/index\.php\/$/i",'',$url_writer->write('',true)),"/");
   		header('Location: '.$base_url.'/install/index.php');
   		exit;
	}

require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setExtensionDelegate('C4_DevblocksExtensionDelegate');

// Request
$request = DevblocksPlatform::readRequest();

// Patches (if not on the patch page)
if(0 != strcasecmp(@$request->path[0],"update") 
	&& !DevblocksPlatform::versionConsistencyCheck()) {
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update','locked')));
		exit;
}

//DevblocksPlatform::readPlugins();
$session = DevblocksPlatform::getSessionService();

// Localization
DevblocksPlatform::setLocale((isset($_SESSION['locale']) && !empty($_SESSION['locale'])) ? $_SESSION['locale'] : 'en_US');
if(isset($_SESSION['timezone'])) @date_default_timezone_set($_SESSION['timezone']);

// Initialize Logging
if(method_exists('DevblocksPlatform','getConsoleLog')) {
	$timeout = ini_get('max_execution_time');
	$logger = DevblocksPlatform::getConsoleLog();
	$logger->info("[Devblocks] ** Platform starting (".date("r").") **");
	$logger->info('[Devblocks] Time Limit: '. (($timeout) ? $timeout : 'unlimited') ." secs");
	$logger->info('[Devblocks] Memory Limit: '. ini_get('memory_limit'));
}

// [JAS]: HTTP Request (App->Platform)
CerberusApplication::processRequest($request);

exit;
