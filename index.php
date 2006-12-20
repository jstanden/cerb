<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/cloudglue/CloudGlue.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');

CgPlatform::init();

$smarty = CgTemplateManager::getInstance();
$session = CgSessionManager::getInstance(); /* @var $session CgSessionManager */
$translate = CgTranslationManager::getInstance();

//$plugins = CgPlatform::readPlugins();

// [JAS]: Handle component actions
@$c = (isset($_REQUEST['c']) ? $_REQUEST['c'] : null);
@$a = (isset($_REQUEST['a']) ? $_REQUEST['a'] : null);

$visit = $session->getVisit();
if(!empty($c) && !empty($a)) {
	// [JAS]: [TODO] Split $c and look for an ID and an instance
	$mfTarget = CgPlatform::getExtension($c);
	$target = $mfTarget->createInstance();

	// [JAS]: Security check
	if(empty($visit)) {
		if (0 != strcasecmp($c,"core.module.signin") && !is_a($target, 'cerberusloginmoduleextension')) {
			// [JAS]: [TODO] This should probably be a meta redirect for IIS.
			header("Location: index.php?c=core.module.signin&a=show");
			exit;
		}
	}
	
	if(method_exists($target,$a)) {
		call_user_method($a,$target); // [JAS]: [TODO] Action Args
	}
}

$activeModule = CerberusApplication::getActiveModule();
if(empty($activeModule)) {
	$visit = $session->getVisit();
	if(empty($visit)) {
		$activeModule = "core.module.signin"; // default?
	} else {
		$activeModule = "core.module.dashboard";	
	}
}

$module = null;
$ext = CgPlatform::getExtension($activeModule);
if(!empty($ext) && !empty($activeModule)) {
	$module = $ext->createInstance(1);
}
$smarty->assign('module',$module);

$modules = CerberusApplication::getModules();
$smarty->assign('modules',$modules);

$index_tokens = array(
	"header_signed_in" => array($visit->login)
);
$smarty->assign('index_tokens',$index_tokens);

$smarty->assign('session', $_SESSION);
$smarty->assign('visit', $session->getVisit());
$smarty->assign('translate', $translate);
$smarty->assign('c', $c);
$smarty->assign('activeModule', $activeModule);

//$smarty->clear_all_cache();
$smarty->caching = 0;
$smarty->display('border.php');
?>