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

class ChReportSpamAddys extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$db = DevblocksPlatform::getDatabaseService();
		
		$top_spam_addys = array();
		$top_nonspam_addys = array();
		
		$sql = "SELECT email,num_spam,num_nonspam,is_banned FROM address WHERE num_spam+num_nonspam > 0 ORDER BY num_spam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_spam_addys[$row['email']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_spam_addys', $top_spam_addys);
		
		mysql_free_result($rs);
		
		$sql = "SELECT email,num_spam,num_nonspam,is_banned FROM address WHERE num_spam+num_nonspam > 0 ORDER BY num_nonspam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_nonspam_addys[$row['email']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_nonspam_addys', $top_nonspam_addys);
		
		mysql_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/spam/spam_addys/index.tpl');
	}
};