<?php
class ChImportCron extends CerberusCronPageExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
	function run() {
	    $manifest = DevblocksPlatform::getExtension('importer.cerb350');
	    $importer = $manifest->createInstance(); /* @var $importer ChCerb350Importer */
        // [TODO] Store the progress in the cache system?
//      $start = $importer->getParam('start', 1);
	    $importer->import();
//	    $importer->setParam('start', $last);
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$tpl->display($tpl_path . 'cron/import/config.tpl.php');
	}
	
	public function saveConfigurationAction() {
	    
	}
    
}

?>