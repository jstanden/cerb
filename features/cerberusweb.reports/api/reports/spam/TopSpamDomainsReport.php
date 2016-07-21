<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerb.io/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://cerb.io	    http://webgroup.media
 ***********************************************************************/

class ChReportSpamDomains extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$db = DevblocksPlatform::getDatabaseService();
		
		$top_spam_domains = array();
		$top_nonspam_domains = array();
		
		$sql = "SELECT count(*) AS hits, SUBSTRING(email,LOCATE('@',email)+1) AS domain, SUM(num_spam) AS num_spam, SUM(num_nonspam) AS num_nonspam FROM address WHERE num_spam+num_nonspam > 0 GROUP BY domain ORDER BY num_spam DESC LIMIT 0,100";
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$top_spam_domains[$row['domain']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_spam_domains', $top_spam_domains);

		mysqli_free_result($rs);
		
		$sql = "SELECT count(*) AS hits, SUBSTRING(email,LOCATE('@',email)+1) AS domain, SUM(num_spam) AS num_spam, SUM(num_nonspam) AS num_nonspam FROM address WHERE num_spam+num_nonspam > 0 GROUP BY domain ORDER BY num_nonspam DESC LIMIT 0,100";
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$top_nonspam_domains[$row['domain']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_nonspam_domains', $top_nonspam_domains);
		
		mysqli_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/spam/spam_domains/index.tpl');
	}
};