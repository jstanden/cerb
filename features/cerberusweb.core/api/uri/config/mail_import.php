<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

class PageSection_SetupMailImport extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$visit->set(ChConfigurationPage::ID, 'mail_import');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_import/index.tpl');
	}
	
	function parseMessageJsonAction() {
		header("Content-Type: application/json");
		
		$logger = DevblocksPlatform::getConsoleLog('Parser');
		$logger->setLogLevel(4);
		
		ob_start();
		
		$log = null;
		
		try {
			@$message_source = DevblocksPlatform::importGPC($_REQUEST['message_source'],'string','');
	
			$dict = CerberusParser::parseMessageSource($message_source, true, true);
			$message = '';
			
			if(is_object($dict) && !empty($dict->id)) {
				$message = sprintf('<b>Ticket updated:</b> <a href="%s">%s</a>',
					$dict->url,
					$dict->_label
				);
				
			} elseif(null === $dict) {
				$log = ob_get_contents();
				
				$message = sprintf('<b>Rejected:</b> %s',
					$log
				);
			}
			
			$json = json_encode(array(
				'status' => true,
				'message' => $message,
			));
			
			ob_end_clean();
			
			echo $json;
			
		} catch (Exception $e) {
			$log = ob_get_contents();
			
			$json = json_encode(array(
				'status' => false,
				'message' => $e->getMessage(),
				'log' => $log,
			));
			
			ob_end_clean();
			
			echo $json;
		}
		
		$logger->setLogLevel(0);
	}
}
