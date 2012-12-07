<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

header("Content-type: text/html; charset=".LANG_CHARSET_CODE);

$request = DevblocksPlatform::readRequest();

DevblocksPlatform::init();
DevblocksPlatform::setExtensionDelegate('C4_DevblocksExtensionDelegate');

$session = DevblocksPlatform::getSessionService();
$settings = DevblocksPlatform::getPluginSettingsService();
$worker = CerberusApplication::getActiveWorker();

// Localization
DevblocksPlatform::setLocale((isset($_SESSION['locale']) && !empty($_SESSION['locale'])) ? $_SESSION['locale'] : 'en_US');
if(isset($_SESSION['timezone'])) @date_default_timezone_set($_SESSION['timezone']);

$tpl = DevblocksPlatform::getTemplateService();
$tpl->assign('translate', DevblocksPlatform::getTranslationService());
$tpl->assign('session', $_SESSION);
$tpl->assign('visit', $session->getVisit());
$tpl->assign('active_worker', $worker);
$tpl->assign('settings', $settings);

if(!empty($worker)) {
	$active_worker_memberships = $worker->getMemberships();
	$tpl->assign('active_worker_memberships', $active_worker_memberships);
	
	$keyboard_shortcuts = intval(DAO_WorkerPref::get($worker->id,'keyboard_shortcuts', 1));
	$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);
}

CerberusApplication::processRequest($request,true);

exit;
