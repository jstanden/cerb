<?php
class DAO_Faq extends DevblocksORMHelper {
    const ID = 'id';
    const QUESTION = 'question';
    const IS_ANSWERED = 'is_answered';
    const ANSWER = 'answer';
    const ANSWERED_BY = 'answered_by';
    const CREATED = 'created';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('faq_seq');
		
		$sql = sprintf("INSERT INTO faq (id,question,is_answered,answer,answered_by,created) ".
		    "VALUES (%d,'',0,'',0,%d)",
		    $id,
		    time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'faq', $fields);
	}
	
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, question, is_answered, answered_by, created ".
		    "FROM faq ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    ""
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = array();
		
		while(!$rs->EOF) {
		    $object = new Model_Faq();
		    $object->id = intval($rs->fields['id']);
		    $object->question = $rs->fields['question'];
		    $object->is_answered = $rs->fields[self::IS_ANSWERED];
		    $object->answered_by = $rs->fields[self::ANSWERED_BY];
//		    $object->answer = $rs->fields['answer'];
		    $object->created = intval($rs->fields['created']);
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function getAnswers($ids=array()) {
        if(!is_array($ids)) $ids = array($ids);
        if(empty($ids)) return;

        $db = DevblocksPlatform::getDatabaseService();
        
        $sql = sprintf("SELECT id, answer FROM faq WHERE id IN (%s)",
            implode(',', $ids)
        );
        $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
        
        $answers = array();
        
        while(!$rs->EOF) {
            $id = intval($rs->fields['id']);
            $answers[$id] = $rs->fields['answer'];
            $rs->MoveNext();
        }
        
        return $answers;        
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE FROM faq WHERE id IN (%s)", $id_list);
	    $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

	    // [TODO] cascade foreign key constraints	
	}

    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Faq::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"f.id as %s, ".
			"f.question as %s ".
			"FROM faq f ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Faq::ID,
			    SearchFields_Faq::QUESTION
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_Faq::ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
};

class SearchFields_Faq implements IDevblocksSearchFields {
	// Table
	const ID = 'f_id';
	const QUESTION = 'f_question';
	const IS_ANSWERED = 'f_is_answered';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_Faq::ID => new DevblocksSearchField(SearchFields_Faq::ID, 'f', 'id'),
			SearchFields_Faq::QUESTION => new DevblocksSearchField(SearchFields_Faq::QUESTION, 'f', 'question'),
			SearchFields_Faq::IS_ANSWERED => new DevblocksSearchField(SearchFields_Faq::IS_ANSWERED, 'f', 'is_answered'),
		);
	}
};	


?>