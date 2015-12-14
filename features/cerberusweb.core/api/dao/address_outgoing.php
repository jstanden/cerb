<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
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

class DAO_AddressOutgoing extends Cerb_ORMHelper {
	const ADDRESS_ID = 'address_id';
	const IS_DEFAULT = 'is_default';
	const REPLY_PERSONAL = 'reply_personal';
	const REPLY_SIGNATURE = 'reply_signature';
	const REPLY_HTML_TEMPLATE_ID = 'reply_html_template_id';
	const REPLY_MAIL_TRANSPORT_ID = 'reply_mail_transport_id';
	
	const _CACHE_ALL = 'dao_address_outgoing_all';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = $fields[self::ADDRESS_ID];
		
		if(empty($id))
			return false;
		
		$sql = sprintf("INSERT IGNORE INTO address_outgoing (address_id) ".
			"VALUES (%d)",
			$id
		);
		$db->ExecuteMaster($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		self::_update($ids, 'address_outgoing', $fields, 'address_id');
		self::clearCache();
	}
	
	/**
	 * Cache from master
	 * 
	 * @param boolean $nocache
	 * @return Model_AddressOutgoing[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();

		if($nocache || null === ($froms = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$froms = array();
			
			$sql = "SELECT ao.address_id, a.email, ao.is_default, ao.reply_personal, ao.reply_signature, ao.reply_html_template_id, ao.reply_mail_transport_id ".
				"FROM address_outgoing AS ao ".
				"INNER JOIN address AS a ON (a.id=ao.address_id) ".
				"ORDER BY a.email ASC "
				;
			$rs = $db->ExecuteMaster($sql);
			
			$froms = self::_getObjectsFromResultSet($rs);
			
			$cache->save($froms, self::_CACHE_ALL);
		}
		
		return $froms;
	}
	
	/**
	 *
	 * @return Model_AddressOutgoing
	 */
	static public function getDefault() {
		$froms = self::getAll();
		
		if(!is_array($froms) || empty($froms))
			return null;
		
		// [TODO] This could be used if we don't have any reply addresses
		//$default = new Model_AddressOutgoing();
		//$default->address_id = 0;
		//$default->email = 'do-not-reply@localhost';
		
		// Use the default reply-to
		foreach($froms as $from) {
			if($from->is_default)
				return $from;
		}
		
		// If we got this far, it means we don't have a default. Use the first address.
		$from = reset($froms); /* @var $from Model_AddressOutgoing */
		$from->is_default = 1;
		
		DAO_AddressOutgoing::setDefault($from->address_id);
		
		return $from;
	}
	
	static public function setDefault($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster("UPDATE address_outgoing SET is_default = 0");
		$db->ExecuteMaster(sprintf("UPDATE address_outgoing SET is_default = 1 WHERE address_id = %d", $address_id));
		
		self::clearCache();
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_AddressOutgoing|null
	 */
	static public function get($id) {
		$addresses = self::getAll();
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;
	}
	
	/**
	 * 
	 * @param string $email
	 * @return Model_AddressOutgoing|false
	 */
	static public function getByEmail($email, $with_default=true) {
		$addresses = DAO_AddressOutgoing::getAll();
		
		foreach($addresses as $address) {
			if(0 == strcasecmp($address->email, $email))
				return $address;
		}
		
		if($with_default) {
			return DAO_AddressOutgoing::getDefault();
		} else {
			return false;
		}
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AddressOutgoing();
			$object->address_id = intval($row['address_id']);
			$object->email = $row['email'];
			$object->is_default = intval($row['is_default']);
			$object->reply_personal = $row['reply_personal'];
			$object->reply_signature = $row['reply_signature'];
			$object->reply_html_template_id = intval($row['reply_html_template_id']);
			$object->reply_mail_transport_id = intval($row['reply_mail_transport_id']);
			$objects[$object->address_id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function isLocalAddress($address) {
		$helpdesk_froms = DAO_AddressOutgoing::getAll();
		foreach($helpdesk_froms as $from) {
			if(0 == strcasecmp($from->email, $address))
				return true;
		}
		
		return false;
	}
	
	static function isLocalAddressId($id) {
		$helpdesk_froms = DAO_AddressOutgoing::getAll();
		foreach($helpdesk_froms as $from_id => $from) {
			if(intval($from_id)==intval($id))
				return true;
		}
		
		return false;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// Delete this address_outgoing row
		$db->ExecuteMaster(sprintf("DELETE FROM address_outgoing WHERE address_id IN (%s)", $ids_list));
		
		// Reset buckets
		$db->ExecuteMaster(sprintf("UPDATE bucket SET reply_address_id=0 WHERE reply_address_id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return TRUE;
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
		
		DAO_Group::clearCache();
		DAO_Bucket::clearCache();
	}
};

class Model_AddressOutgoing {
	public $address_id;
	public $email;
	public $is_default = 0;
	public $reply_personal = '';
	public $reply_signature = '';
	public $reply_html_template_id = 0;
	public $reply_mail_transport_id = 0;
	
	/**
	 * 
	 * @param Model_Worker|NULL $worker_model
	 * @return string
	 */
	function getReplyPersonal($worker_model=null) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$token_labels = array();
		$token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
		return $tpl_builder->build($this->reply_personal, $token_values);
	}
	
	/**
	 * 
	 * @return Model_MailHtmlTemplate|NULL
	 */
	function getReplyHtmlTemplate() {
		if($this->reply_html_template_id && false != ($html_template = DAO_MailHtmlTemplate::get($this->reply_html_template_id)))
			return $html_template;

		if(empty($this->is_default) && false != ($replyto_default = DAO_AddressOutgoing::getDefault())) {
			if($replyto_default->reply_html_template_id && false != ($html_template = DAO_MailHtmlTemplate::get($replyto_default->reply_html_template_id)))
				return $html_template;
		}
		
		return null;
	}
	
	/**
	 * 
	 * @param Model_Worker|NULL $worker_model
	 * @return string
	 */
	function getReplySignature($worker_model=null) {
		$sig = '';
		
		// Try the default reply-to
		if(!empty($this->reply_signature)) {
			$sig = $this->reply_signature;
		} else {
			if(empty($this->is_default)) { // Don't recurse
				$replyto_default = DAO_AddressOutgoing::getDefault();
				$sig = $replyto_default->getReplySignature($model_worker);
			}
		}
		
		if(empty($worker_model)) {
			return $sig;
			
		} else {
			// Parse template
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			$token_labels = array();
			$token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
			return $tpl_builder->build($sig, $token_values);
		}
	}
	
	/**
	 * 
	 * @return Model_MailTransport|NULL
	 */
	function getReplyMailTransport() {
		// If this reply-to has an explicit mail transport set
		if($this->reply_mail_transport_id && false != ($mail_transport = DAO_MailTransport::get($this->reply_mail_transport_id))) {
			return $mail_transport;
		}
		
		// Otherwise, if this isn't the default reply-to then check the default
		if(!$this->is_default 
			&& false != ($replyto_default = DAO_AddressOutgoing::getDefault())
			&& false != ($mail_transport = $replyto_default->getReplyMailTransport())) {
				return $mail_transport;
		}

		// Lastly, just use the default reply-to
		if(false !== ($mail_transport = DAO_MailTransport::getDefault())) {
			return $mail_transport;
		}
		
		return null;
	}
};