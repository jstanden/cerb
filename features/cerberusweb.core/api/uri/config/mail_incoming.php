<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class PageSection_SetupMailIncoming extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$settings = $db = DevblocksPlatform::services()->pluginSettings();
		
		$visit->set(ChConfigurationPage::ID, 'mail_incoming');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$translate = DevblocksPlatform::getTranslationService();
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not an administrator.");
			
			@$parser_autoreq = DevblocksPlatform::importGPC($_POST['parser_autoreq'],'integer',0);
			@$parser_autoreq_exclude = DevblocksPlatform::importGPC($_POST['parser_autoreq_exclude'],'string','');
			@$attachments_enabled = DevblocksPlatform::importGPC($_POST['attachments_enabled'],'integer',0);
			@$attachments_max_size = DevblocksPlatform::importGPC($_POST['attachments_max_size'],'integer',10);
			@$ticket_mask_format = DevblocksPlatform::importGPC($_POST['ticket_mask_format'],'string','');
			@$html_no_strip_microsoft = DevblocksPlatform::importGPC($_POST['html_no_strip_microsoft'],'integer',0);
			
			if(empty($ticket_mask_format))
				$ticket_mask_format = 'LLL-NNNNN-NNN';
				
			// Count the number of combinations in ticket mask pattern
			
			$cardinality = CerberusApplication::generateTicketMaskCardinality($ticket_mask_format);
			if($cardinality < 10000000)
				throw new Exception(sprintf("<b>Error!</b> There are only %s ticket mask combinations.",
					strrev(implode(',',str_split(strrev($cardinality),3)))
				));
			
			// Save
			
			$settings = $db = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ, $parser_autoreq);
			$settings->set('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, $parser_autoreq_exclude);
			$settings->set('cerberusweb.core',CerberusSettings::ATTACHMENTS_ENABLED, $attachments_enabled);
			$settings->set('cerberusweb.core',CerberusSettings::ATTACHMENTS_MAX_SIZE, $attachments_max_size);
			$settings->set('cerberusweb.core',CerberusSettings::TICKET_MASK_FORMAT, $ticket_mask_format);
			$settings->set('cerberusweb.core',CerberusSettings::HTML_NO_STRIP_MICROSOFT, $html_no_strip_microsoft);
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
		
	}
	
	function testMaskAction() {
		@$ticket_mask_format = DevblocksPlatform::importGPC($_POST['ticket_mask_format'],'string','');
		
		try {
			$cardinality = CerberusApplication::generateTicketMaskCardinality($ticket_mask_format);
			if($cardinality < 10000000)
				throw new Exception(sprintf("<b>Error!</b> There are only %s ticket mask combinations.",
					strrev(implode(',',str_split(strrev($cardinality),3)))
				));
			
			$sample_mask = CerberusApplication::generateTicketMask($ticket_mask_format);
			$output = sprintf("<b>%s</b> &nbsp; There are %s possible ticket mask combinations.",
				$sample_mask,
				strrev(implode(',',str_split(strrev($cardinality),3)))
			);
			echo json_encode(array('status'=>true,'message'=>$output));
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
		
	}
	
}