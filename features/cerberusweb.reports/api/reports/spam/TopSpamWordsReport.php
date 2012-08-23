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

class ChReportSpamWords extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT spam, nonspam FROM bayes_stats";
		if(null != ($row = $db->GetRow($sql))) {
			$num_spam = $row['spam'];
			$num_nonspam = $row['nonspam'];
		}
		
		$tpl->assign('num_spam', intval($num_spam));
		$tpl->assign('num_nonspam', intval($num_nonspam));
		
		$top_spam_words = array();
		$top_nonspam_words = array();
		
		$sql = "SELECT word,spam,nonspam FROM bayes_words ORDER BY spam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_spam_words[$row['word']] = array($row['spam'], $row['nonspam']);
		}
		$tpl->assign('top_spam_words', $top_spam_words);
		
		mysql_free_result($rs);
		
		$sql = "SELECT word,spam,nonspam FROM bayes_words ORDER BY nonspam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_nonspam_words[$row['word']] = array($row['spam'], $row['nonspam']);
		}
		$tpl->assign('top_nonspam_words', $top_nonspam_words);
		
		mysql_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/spam/spam_words/index.tpl');
	}
};