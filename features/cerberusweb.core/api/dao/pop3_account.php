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

class DAO_Pop3Account {
	const ID = 'id';
	const ENABLED = 'enabled';
	const NICKNAME = 'nickname';
	const PROTOCOL = 'protocol';
	const HOST = 'host';
	const USERNAME = 'username';
	const PASSWORD = 'password';
	const PORT = 'port';
	const NUM_FAILS = 'num_fails';
	const DELAY_UNTIL = 'delay_until';
	const TIMEOUT_SECS = 'timeout_secs';
	const MAX_MSG_SIZE_KB = 'max_msg_size_kb';
	const SSL_IGNORE_VALIDATION = 'ssl_ignore_validation';
	
	static function createPop3Account($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO pop3_account () ".
			"VALUES ()"
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId();
		
		self::updatePop3Account($id, $fields);
		
		return $id;
	}
	
	static function getPop3Accounts($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		$pop3accounts = array();
		
		$sql = "SELECT id, enabled, nickname, protocol, host, username, password, port, num_fails, delay_until, timeout_secs, max_msg_size_kb, ssl_ignore_validation ".
			"FROM pop3_account ".
			((!empty($ids) ? sprintf("WHERE id IN (%s)", implode(',', $ids)) : " ").
			"ORDER BY nickname "
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		while($row = mysqli_fetch_assoc($rs)) {
			$pop3 = new Model_Pop3Account();
			$pop3->id = intval($row['id']);
			$pop3->enabled = intval($row['enabled']);
			$pop3->nickname = $row['nickname'];
			$pop3->protocol = $row['protocol'];
			$pop3->host = $row['host'];
			$pop3->username = $row['username'];
			$pop3->password = $row['password'];
			$pop3->port = intval($row['port']);
			$pop3->num_fails = intval($row['num_fails']);
			$pop3->delay_until = intval($row['delay_until']);
			$pop3->timeout_secs = intval($row['timeout_secs']);
			$pop3->max_msg_size_kb = intval($row['max_msg_size_kb']);
			$pop3->ssl_ignore_validation = intval($row['ssl_ignore_validation']);
			$pop3accounts[$pop3->id] = $pop3;
		}
		
		mysqli_free_result($rs);
		
		return $pop3accounts;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Pop3Account
	 */
	static function getPop3Account($id) {
		$accounts = DAO_Pop3Account::getPop3Accounts(array($id));
		
		if(isset($accounts[$id]))
			return $accounts[$id];
			
		return null;
	}
	
	static function updatePop3Account($id, $fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE pop3_account SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	static function deletePop3Account($id) {
		if(empty($id))
			return;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM pop3_account WHERE id = %d",
			$id
		);
		
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
};

class Model_Pop3Account {
	public $id;
	public $enabled=1;
	public $nickname;
	public $protocol='pop3';
	public $host;
	public $username;
	public $password;
	public $port=110;
	public $num_fails = 0;
	public $delay_until = 0;
	public $timeout_secs = 30;
	public $max_msg_size_kb = 25600;
	public $ssl_ignore_validation = 0;
	
	function getImapConnectString() {
		$connect = null;
		
		switch($this->protocol) {
			default:
			case 'pop3': // 110
				$connect = sprintf("{%s:%d/pop3/notls}INBOX",
					$this->host,
					$this->port
				);
				break;
				 
			case 'pop3-ssl': // 995
				$connect = sprintf("{%s:%d/pop3/ssl%s}INBOX",
					$this->host,
					$this->port,
					$this->ssl_ignore_validation ? '/novalidate-cert' : ''
				);
				break;
				 
			case 'imap': // 143
				$connect = sprintf("{%s:%d/notls}INBOX",
					$this->host,
					$this->port
				);
				break;
	
			case 'imap-ssl': // 993
				$connect = sprintf("{%s:%d/imap/ssl%s}INBOX",
					$this->host,
					$this->port,
					$this->ssl_ignore_validation ? '/novalidate-cert' : ''
				);
				break;
		}
		
		return $connect;
	}
};