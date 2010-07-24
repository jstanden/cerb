<?php
class ChReportOrgSharedEmailDomains extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(DISTINCT a.contact_org_id) AS num_orgs, substring(a.email,locate('@',a.email)+1) AS domain ".
			"FROM address a ".
			"INNER JOIN contact_org o ON (a.contact_org_id=o.id) ".
			"WHERE a.contact_org_id != 0 ".
			"GROUP BY domain ".
			"HAVING num_orgs > 1 ".
			"ORDER BY num_orgs desc ".
			"LIMIT 0,100"
		);
		$rs = $db->Execute($sql);
		
		$top_domains = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_domains[$row['domain']] = intval($row['num_orgs']);
		}
		$tpl->assign('top_domains', $top_domains);
		
		mysql_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/org/shared_email_domains/index.tpl');
	}
};