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
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

header("Content-type: text/html; charset=".LANG_CHARSET_CODE);

$request = DevblocksPlatform::readRequest();

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');

$tpl = DevblocksPlatform::services()->template();

DevblocksPlatform::setStateless(in_array($request->path[0] ?? [], ['cron','portal','resource']));

if(DevblocksPlatform::isStateless()) {
	$_SESSION = [];
	
} else {
	$session = DevblocksPlatform::services()->session();
	$tpl->assign('session', $_SESSION);
	$tpl->assign('visit', $session->getVisit());
}

$settings = DevblocksPlatform::services()->pluginSettings();
$worker = CerberusApplication::getActiveWorker();

// Localization
DevblocksPlatform::setDateTimeFormat(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::TIME_FORMAT, CerberusSettingsDefaults::TIME_FORMAT));

// Locale
DevblocksPlatform::setLocale((isset($_SESSION['locale']) && !empty($_SESSION['locale'])) ? $_SESSION['locale'] : 'en_US');

// Timezone
DevblocksPlatform::setTimezone();

// Time format
if(isset($_SESSION['time_format']))
	DevblocksPlatform::setDateTimeFormat($_SESSION['time_format']);

// Scope
$tpl->assign('translate', DevblocksPlatform::getTranslationService());
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