<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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

class ChContactsPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == CerberusApplication::getActiveWorker())
			return false;
		
		return true;
	}
	
	function render() {
	}
	
	function viewAddysExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=contacts&tab=addresses', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $org_id => $row) {
				if($org_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Address::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=address&id=%d-%s", $row[SearchFields_Address::ID], $row[SearchFields_Address::EMAIL]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function getTopContactsByOrgJsonAction() {
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string');
		
		header('Content-type: text/json');
		
		if(empty($org_name) || null == ($org_id = DAO_ContactOrg::lookup($org_name, false))) {
			echo json_encode(array());
			exit;
		}
		
		// Match org, ignore banned
		$results = DAO_Address::getWhere(
			sprintf("%s = %d AND %s = %d AND %s = %d",
				DAO_Address::CONTACT_ORG_ID,
				$org_id,
				DAO_Address::IS_BANNED,
				0,
				DAO_Address::IS_DEFUNCT,
				0
			),
			DAO_Address::NUM_NONSPAM,
			true,
			25
		);
		
		$list = array();
		
		foreach($results as $result) { /* @var $result Model_Address */
			$list[] = array(
				'id' => $result->id,
				'email' => $result->email,
				'name' => DevblocksPlatform::strEscapeHtml($result->getName()),
			);
		}
		
		echo json_encode($list);
		
		exit;
	}
	
	function viewOrgsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&tab=org', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $org_id => $row) {
				if($org_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ContactOrg::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=org&id=%d", $row[SearchFields_ContactOrg::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function showOrgMergePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_ids = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($org_ids)) {
			$org_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($org_ids),'integer',array('nonzero','unique'));
			
			if(!empty($org_ids)) {
				$orgs = DAO_ContactOrg::getWhere(sprintf("%s IN (%s)",
					DAO_ContactOrg::ID,
					implode(',', $org_ids)
				));
				
				$tpl->assign('orgs', $orgs);
			}
		}
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_merge_peek.tpl');
	}
	
	function getOrgsAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		
		list($orgs,) = DAO_ContactOrg::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE, $starts_with. '*'),
			),
			25,
			0,
			SearchFields_ContactOrg::NAME,
			true,
			false
		);
		
		$list = array();
		
		foreach($orgs AS $val){
			$list[] = $val[SearchFields_ContactOrg::NAME];
		}
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
	
	function getCountryAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT DISTINCT country AS country ".
			"FROM contact_org ".
			"WHERE country != '' ".
			"AND country LIKE %s ".
			"ORDER BY country ASC ".
			"LIMIT 0,25",
			$db->qstr($starts_with.'%')
		);
		
		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;
		
		$list = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$list[] = $row['country'];
		}
		
		mysqli_free_result($rs);
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
	
};
