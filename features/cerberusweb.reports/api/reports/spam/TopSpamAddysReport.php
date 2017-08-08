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

class ChReportSpamAddys extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$db = DevblocksPlatform::services()->database();
		
		$top_spam_addys = array();
		$top_nonspam_addys = array();
		
		$sql = "SELECT id,email,num_spam,num_nonspam,is_banned FROM address WHERE num_spam+num_nonspam > 0 ORDER BY num_spam desc LIMIT 0,100";
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$top_spam_addys[$row['email']] = array('id'=>$row['id'], 'counts'=>array($row['num_spam'], $row['num_nonspam'], $row['is_banned']));
		}
		$tpl->assign('top_spam_addys', $top_spam_addys);
		
		mysqli_free_result($rs);
		
		$sql = "SELECT id,email,num_spam,num_nonspam,is_banned FROM address WHERE num_spam+num_nonspam > 0 ORDER BY num_nonspam desc LIMIT 0,100";
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$top_nonspam_addys[$row['email']] = array('id'=>$row['id'], 'counts'=>array($row['num_spam'], $row['num_nonspam'], $row['is_banned']));
		}
		$tpl->assign('top_nonspam_addys', $top_nonspam_addys);
		
		mysqli_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/spam/spam_addys/index.tpl');
	}
};