<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChRest_Opps extends Extension_RestController implements IExtensionRestController {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'note':
					$this->postNote($id);
					break;
			}

		} else {
			switch($action) {
				case 'create':
					$this->postCreate();
					break;
				case 'search':
					$this->postSearch();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $labels, $values, null, true);

//		unset($values['initial_message_content']);
		
		return $values;
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'amount' => DAO_CrmOpportunity::AMOUNT,
//				'assignee_id' => DAO_CrmOpportunity::WORKER_ID,
				'created' => DAO_CrmOpportunity::CREATED_DATE,
				'email_id' => DAO_CrmOpportunity::PRIMARY_EMAIL_ID,
				'is_closed' => DAO_CrmOpportunity::IS_CLOSED,
				'is_won' => DAO_CrmOpportunity::IS_WON,
				'title' => DAO_CrmOpportunity::NAME,
				'updated' => DAO_CrmOpportunity::UPDATED_DATE,
			);
		} else {
			$tokens = array(
				'amount' => SearchFields_CrmOpportunity::AMOUNT,
				'created' => SearchFields_CrmOpportunity::CREATED_DATE,
				'email_address' => SearchFields_CrmOpportunity::EMAIL_ADDRESS,
				'email_id' => SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
				'id' => SearchFields_CrmOpportunity::ID,
				'is_closed' => SearchFields_CrmOpportunity::IS_CLOSED,
				'is_won' => SearchFields_CrmOpportunity::IS_WON,
				'id' => SearchFields_CrmOpportunity::ID,
				'title' => SearchFields_CrmOpportunity::NAME,
				'updated' => SearchFields_CrmOpportunity::UPDATED_DATE,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}		
	
	function getId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('plugin.cerberusweb.crm'))
			$this->error(self::ERRNO_ACL);
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid opportunity id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_CrmOpportunity::search(
			array(),
			$params,
			$limit,
			max(0,$page-1),
			$sortBy,
			$sortAsc,
			true
		);
		
		$objects = array();
		
		foreach($results as $id => $result) {
			$values = $this->getContext($id);
			$objects[$id] = $values;
		}
		
		$container = array(
			'total' => $total,
			'count' => count($objects),
			'page' => $page,
			'results' => $objects,
		);
		
		return $container;		
	}	
	
	function postSearch() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('plugin.cerberusweb.crm'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// Validate the ID
		if(null == ($opp = DAO_CrmOpportunity::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid opportunity ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('crm.opp.actions.update_all') || $opp->worker_id==$worker->id))
			$this->error(self::ERRNO_ACL);
		
		$putfields = array(
			'amount' => 'float',
			'assignee_id' => 'integer',
			'created' => 'timestamp',
			'email_address' => 'string',
			'email_id' => 'integer',
			'is_closed' => 'bit',
			'is_won' => 'bit',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();

		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);

			// Pre-filter
			switch($putfield) {
				case 'email_address':
					if(null != ($lookup = DAO_Address::lookupAddress($value, true))) {
						unset($putfields['email_id']);
						$putfield = 'email_id';
						$value = $lookup->id;
					}
					break;
			}
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
						
			// Post-filter
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
			$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_CrmOpportunity::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('crm.opp.actions.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'amount' => 'float',
			'assignee_id' => 'integer',
			'created' => 'timestamp',
			'email_address' => 'string',
			'email_id' => 'integer',
			'is_closed' => 'bit',
			'is_won' => 'bit',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
				
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			// Pre-filter
			switch($postfield) {
				case 'email_address':
					if(null != ($lookup = DAO_Address::lookupAddress($value, true))) {
						unset($postfields['email_id']);
						$postfield = 'email_id';
						$value = $lookup->id;
					} 
					break;
			}
			
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}
			
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_CrmOpportunity::CREATED_DATE]))
			$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
		if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
			$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_CrmOpportunity::NAME, 
			DAO_CrmOpportunity::PRIMARY_EMAIL_ID,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_CrmOpportunity::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
	
	private function postNote($id) {
		$worker = $this->getActiveWorker();

		@$note = DevblocksPlatform::importGPC($_POST['note'],'string','');
		
		if(null == ($opp = DAO_CrmOpportunity::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid opp ID %d", $id));

		// ACL
		if(!$worker->hasPriv('core.addybook.org.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = array(
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
			DAO_Comment::CONTEXT_ID => $opp->id,
			DAO_Comment::ADDRESS_ID => $worker->getAddress()->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		);
		$note_id = DAO_Comment::create($fields);
			
		$this->success(array(
			'opp_id' => $opp->id,
			'note_id' => $note_id,
		));
	}	
};