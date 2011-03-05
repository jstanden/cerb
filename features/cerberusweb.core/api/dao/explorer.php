<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
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
class DAO_ExplorerSet {
	const HASH = 'hash';	
	const POS = 'pos';	
	const PARAMS_JSON = 'params_json';
	
	static function createFromModels($models) {
		// Polymorph
		if(!is_array($models) && $models instanceof Model_ExplorerSet)
			$models = array($models);

		if(!is_array($models))
			return false;
			
		$db = DevblocksPlatform::getDatabaseService();

		$values = array();
		
		foreach($models as $model) { /* @var $model Model_ExplorerSet */
			$values[] = sprintf("(%s, %d, %s)",
				$db->qstr($model->hash),
				$model->pos,
				$db->qstr(json_encode($model->params))
			);
		}

		if(empty($values))
			return;
		
		$db->Execute(sprintf("INSERT INTO explorer_set (hash, pos, params_json) ".
			"VALUES %s",
			implode(',', $values)
		));
	}
	
	/**
	 * 
	 * @param string $hash
	 * @param integer $pos
	 * @return Model_ExplorerSet
	 */
	static function get($hash, $pos) {
		if(!is_array($pos))
			$pos = array($pos);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$rs = $db->Execute(sprintf("SELECT hash, pos, params_json ".
			"FROM explorer_set ".
			"WHERE hash = %s ".
			"AND pos IN (%s) ",
			$db->qstr($hash),
			implode(',', $pos)
		));
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	static function set($hash, $params, $pos) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("UPDATE explorer_set SET params_json = %s WHERE hash = %s AND pos = %d",
			$db->qstr(json_encode($params)),
			$db->qstr($hash),
			$pos
		));
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		if(false !== $rs)
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ExplorerSet();
			$object->hash = $row['hash'];
			$object->pos = $row['pos'];
			
			if(!empty($row['params_json'])) {
				if(false !== ($params_json = json_decode($row['params_json'], true)))
					$object->params = $params_json;
			}
			
			$objects[$object->pos] = $object;
		}
		
		return $objects;
	} 
	
	static function update($hash, $params) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("UPDATE explorer_set SET params_json = %s WHERE hash = %s AND pos = 0",
			$db->qstr(json_encode($params)),
			$db->qstr($hash)
		));
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$rs = $db->Execute("SELECT hash, params_json FROM explorer_set WHERE pos = 0");
		
		if(false !== $rs)
		while($row = mysql_fetch_assoc($rs)) {
			if(false !== ($params = @json_decode($row['params_json'], true))) {
				if(!isset($params['last_accessed']) || $params['last_accessed'] < time()-86400) { // idle for 24 hours 
					$db->Execute(sprintf("DELETE FROM explorer_set WHERE hash = %s",
						$db->qstr($row['hash'])
					));
				}
			} 
		}
		
		$logger->info('[Maint] Cleaned up explorer items.');
	}
};

class Model_ExplorerSet {
	public $hash = '';
	public $pos = 0;
	public $params = array();
};
