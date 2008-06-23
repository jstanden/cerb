<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

if(version_compare(PHP_VERSION, "5.1.2", "<"))
	die("Cerberus Helpdesk 4.0 requires PHP 5.1.2 or later.");

require(dirname(__FILE__) . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

// If this is our first run, redirect to the installer
if('' == APP_DB_DRIVER 
	|| '' == APP_DB_HOST 
	|| '' == APP_DB_DATABASE 
	|| null == ($db = DevblocksPlatform::getDatabaseService())
	|| DevblocksPlatform::isDatabaseEmpty()) {
   		header('Location: '.dirname($_SERVER['PHP_SELF']).'/install/index.php'); // [TODO] change this to a meta redirect
   		exit;
	}

require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();

// Request
$request = DevblocksPlatform::readRequest();

// Patches (if not on the patch page)
if(@0 != strcasecmp(@$request->path[0],"update")
	&& !DevblocksPlatform::versionConsistencyCheck())
	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update','locked')));

//DevblocksPlatform::readPlugins();
$session = DevblocksPlatform::getSessionService();

// Localization
if(isset($_SESSION['timezone'])) {
	@date_default_timezone_set($_SESSION['timezone']);
}

// [JAS]: HTTP Request
DevblocksPlatform::processRequest($request);

exit;
?>