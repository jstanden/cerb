<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/ump/UserMeetPlatform.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');
//require(UM_PATH . '/api/UserMeetApplication.class.php');

UserMeetPlatform::init();

$smarty = UserMeetTemplateManager::getInstance();
$session = UserMeetSessionManager::getInstance(); /* @var $session UserMeetSessionManager */
$translate = UserMeetTranslationManager::getInstance();

$plugins = UserMeetPlatform::readPlugins();

// [JAS]: Handle component actions
@$c = (isset($_REQUEST['c']) ? $_REQUEST['c'] : null);
@$a = (isset($_REQUEST['a']) ? $_REQUEST['a'] : null);

if(!empty($c) && !empty($a)) {
	// [JAS]: [TODO] Split $c and look for an ID and an instance
	$mfTarget = UserMeetPlatform::getExtension($c);
	$target = $mfTarget->createInstance();
	
	if(method_exists($target,$a)) {
		call_user_method($a,$target); // [JAS]: [TODO] Action Args
	}
	
	// [JAS]: [TODO] Fix Hack
//	if(is_a($target,"UserMeetMenuExtension")) {
//		UserMeetApplication::setActiveMenu($target->manifest->id);
//	}
//	if(is_a($target,"UserMeetExtension")) {
//		CerberusApplication::setActiveModule($target->manifest->id);
//	}
}

// [JAS]: Leave this below the component introspection
//$pages = UserMeetApplication::getPages();
//$menu = UserMeetApplication::getMenu();

$module = null;
//$activeMenu = CerberusApplication::getActiveMenu();
$activeModule = CerberusApplication::getActiveModule();

if(empty($activeModule)) {
//	$activeMenu = "core.menu.dashboard"; // default?	
	$activeModule = "core.module.dashboard"; // default?	
}

$ext = UserMeetPlatform::getExtension($activeModule);
if(!empty($ext) && !empty($activeModule)) {
//		$instId = (!empty($activeModule['instance_id'])) ? $activeModule['instance_id'] : null;
//		$module = $ext->createInstance($inst);
	$module = $ext->createInstance(1);
}

$smarty->assign('module',$module);

$modules = CerberusApplication::getModules();
$smarty->assign('modules',$modules);
//print_r($modules);
//$menus = CerberusApplication::getMenus();
//$smarty->assign('menus',$menus);

$smarty->assign('session', $_SESSION);
$smarty->assign('visit', $session->getVisit());
$smarty->assign('translate', $translate);
//$smarty->assign('class', $page);
$smarty->assign('c', $c);
//$smarty->assign('activeMenu', $activeMenu);
$smarty->assign('activeModule', $activeModule);
//$smarty->assign('menu', $menu);
//$smarty->assign('pages', $pages);

//$smarty->clear_all_cache();
$smarty->caching = 0;
$smarty->display('border.php');
?>