<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2013, WebGroup Media LLC
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

class ChReportSpamDomains extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$db = DevblocksPlatform::getDatabaseService();
		
		$top_spam_domains = array();
		$top_nonspam_domains = array();
		
		$sql = "select count(*) as hits, substring(email,locate('@',email)+1) as domain, sum(num_spam) as num_spam, sum(num_nonspam) as num_nonspam from address where num_spam+num_nonspam > 0 group by domain order by num_spam desc limit 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_spam_domains[$row['domain']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_spam_domains', $top_spam_domains);

		mysql_free_result($rs);
		
		$sql = "select count(*) as hits, substring(email,locate('@',email)+1) as domain, sum(num_spam) as num_spam, sum(num_nonspam) as num_nonspam from address where num_spam+num_nonspam > 0 group by domain order by num_nonspam desc limit 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_nonspam_domains[$row['domain']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_nonspam_domains', $top_nonspam_domains);
		
		mysql_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/spam/spam_domains/index.tpl');
	}
};