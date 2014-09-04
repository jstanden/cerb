<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupMailHtml extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'mail_html');

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'config_mail_html_templates';
		$defaults->name = 'Search Results';
		$defaults->class_name = 'View_MailHtmlTemplate';
		$defaults->view_columns = array(
			SearchFields_MailHtmlTemplate::NAME,
			SearchFields_MailHtmlTemplate::UPDATED_AT,
			//SearchFields_MailHtmlTemplate::VIRTUAL_OWNER,
		);
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			C4_AbstractViewLoader::setView($view->id, $view);
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_html/index.tpl');
	}
	
}