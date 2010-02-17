<?php
class DAO_Attachment extends DevblocksORMHelper {
    const ID = 'id';
    const MESSAGE_ID = 'message_id';
    const DISPLAY_NAME = 'display_name';
    const MIME_TYPE = 'mime_type';
    const FILE_SIZE = 'file_size';
    const FILEPATH = 'filepath';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('attachment_seq');
		
		$sql = sprintf("INSERT INTO attachment (id,message_id,display_name,mime_type,file_size,filepath) ".
		    "VALUES (%d,0,'','',0,'')",
		    $id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'attachment', $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Attachment
	 */
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_Attachment[]
	 */
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,message_id,display_name,mime_type,file_size,filepath ".
		    "FROM attachment ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    ""
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
		    $object = new Model_Attachment();
		    $object->id = intval($row['id']);
		    $object->message_id = intval($row['message_id']);
		    $object->display_name = $row['display_name'];
		    $object->filepath = $row['filepath'];
		    $object->mime_type = $row['mime_type'];
		    $object->file_size = intval($row['file_size']);
		    $objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * returns an array of Model_Attachment that
	 * correspond to the supplied message id.
	 *
	 * @param integer $id
	 * @return Model_Attachment[]
	 */
	static function getByMessageId($id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id, a.message_id, a.display_name, a.filepath, a.file_size, a.mime_type ".
			"FROM attachment a ".
			"WHERE a.message_id = %d",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$attachments = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$attachment = new Model_Attachment();
			$attachment->id = intval($row['id']);
			$attachment->message_id = intval($row['message_id']);
			$attachment->display_name = $row['display_name'];
			$attachment->filepath = $row['filepath'];
			$attachment->file_size = intval($row['file_size']);
			$attachment->mime_type = $row['mime_type'];
			$attachments[$attachment->id] = $attachment;
		}
		
		mysql_free_result($rs);

		return $attachments;
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "SELECT filepath FROM attachment LEFT JOIN message ON attachment.message_id = message.id WHERE message.id IS NULL";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$attachment_path = APP_STORAGE_PATH . '/attachments/';
		
		// Delete the physical files
		
		while($row = mysql_fetch_assoc($rs)) {
			@unlink($attachment_path . $row['filepath']);
		}
		
		mysql_free_result($rs);
		
		$sql = "DELETE attachment FROM attachment LEFT JOIN message ON attachment.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' attachment records.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("SELECT filepath FROM attachment WHERE id IN (%s)", implode(',',$ids));
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$attachment_path = APP_STORAGE_PATH . '/attachments/';
		
		// Delete the physical files
		
		while($row = mysql_fetch_assoc($rs)) {
			@unlink($attachment_path . $row['filepath']);
		}
		
		mysql_free_result($rs);
		
		// Delete DB manifests
		$sql = sprintf("DELETE attachment FROM attachment WHERE id IN (%s)", implode(',', $ids));
		$db->Execute($sql);
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
		$fields = SearchFields_Attachment::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.message_id as %s, ".
			"a.display_name as %s, ".
			"a.mime_type as %s, ".
			"a.file_size as %s, ".
			"a.filepath as %s, ".
		
			"m.address_id as %s, ".
			"m.created_date as %s, ".
			"m.is_outgoing as %s, ".
		
			"t.id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
		
			"ad.email as %s ".
		
			"FROM attachment a ".
			"INNER JOIN message m ON (a.message_id = m.id) ".
			"INNER JOIN ticket t ON (m.ticket_id = t.id) ".
			"INNER JOIN address ad ON (m.address_id = ad.id) ".
			"",
			    SearchFields_Attachment::ID,
			    SearchFields_Attachment::MESSAGE_ID,
			    SearchFields_Attachment::DISPLAY_NAME,
			    SearchFields_Attachment::MIME_TYPE,
			    SearchFields_Attachment::FILE_SIZE,
			    SearchFields_Attachment::FILEPATH,
			    
			    SearchFields_Attachment::MESSAGE_ADDRESS_ID,
			    SearchFields_Attachment::MESSAGE_CREATED_DATE,
			    SearchFields_Attachment::MESSAGE_IS_OUTGOING,
			    
			    SearchFields_Attachment::TICKET_ID,
			    SearchFields_Attachment::TICKET_MASK,
			    SearchFields_Attachment::TICKET_SUBJECT,
			    
			    SearchFields_Attachment::ADDRESS_EMAIL
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_Attachment::ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = mysql_num_rows($rs);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
	
};

class SearchFields_Attachment implements IDevblocksSearchFields {
    const ID = 'a_id';
    const MESSAGE_ID = 'a_message_id';
    const DISPLAY_NAME = 'a_display_name';
    const MIME_TYPE = 'a_mime_type';
    const FILE_SIZE = 'a_file_size';
    const FILEPATH = 'a_filepath';
	
    const MESSAGE_ADDRESS_ID = 'm_address_id';
    const MESSAGE_CREATED_DATE = 'm_created_date';
    const MESSAGE_IS_OUTGOING = 'm_is_outgoing';
    
    const TICKET_ID = 't_id';
    const TICKET_MASK = 't_mask';
    const TICKET_SUBJECT = 't_subject';
    
    const ADDRESS_EMAIL = 'ad_email';
    
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('attachment.id')),
			self::MESSAGE_ID => new DevblocksSearchField(self::MESSAGE_ID, 'a', 'message_id', $translate->_('attachment.message_id')),
			self::DISPLAY_NAME => new DevblocksSearchField(self::DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name')),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type')),
			self::FILE_SIZE => new DevblocksSearchField(self::FILE_SIZE, 'a', 'file_size', $translate->_('attachment.file_size')),
			self::FILEPATH => new DevblocksSearchField(self::FILEPATH, 'a', 'filepath', $translate->_('attachment.filepath')),
			
			self::MESSAGE_ADDRESS_ID => new DevblocksSearchField(self::MESSAGE_ADDRESS_ID, 'm', 'address_id'),
			self::MESSAGE_CREATED_DATE => new DevblocksSearchField(self::MESSAGE_CREATED_DATE, 'm', 'created_date', $translate->_('message.created_date')),
			self::MESSAGE_IS_OUTGOING => new DevblocksSearchField(self::MESSAGE_IS_OUTGOING, 'm', 'is_outgoing', $translate->_('mail.outbound')),
			
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 't', 'id', $translate->_('ticket.id')),
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 't', 'mask', $translate->_('ticket.mask')),
			self::TICKET_SUBJECT => new DevblocksSearchField(self::TICKET_SUBJECT, 't', 'subject', $translate->_('ticket.subject')),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'ad', 'email', $translate->_('message.header.from')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Attachment {
	public $id;
	public $message_id;
	public $display_name;
	public $filepath;
	public $file_size = 0;
	public $mime_type = '';

	public function getFileContents() {
		$file_path = APP_STORAGE_PATH . '/attachments/';
		if (!empty($this->filepath))
		return file_get_contents($file_path.$this->filepath,false);
	}
	
	public function getFileSize() {
		$file_path = APP_STORAGE_PATH . '/attachments/';
		if (!empty($this->filepath))
		return filesize($file_path.$this->filepath);
	}
	
	public static function saveToFile($file_id, $contents) {
		$attachment_path = APP_STORAGE_PATH . '/attachments/';
		
	    // Make file attachments use buckets so we have a max per directory
		$attachment_bucket = sprintf("%03d/",
			mt_rand(1,100)
		);
		$attachment_file = $file_id;
		
		if(!file_exists($attachment_path.$attachment_bucket)) {
			@mkdir($attachment_path.$attachment_bucket, 0770, true);
			// [TODO] Needs error checking
		}
		
		file_put_contents($attachment_path.$attachment_bucket.$attachment_file, $contents);
		
		return $attachment_bucket.$attachment_file;
	}
};

class View_Attachment extends C4_AbstractView {
	const DEFAULT_ID = 'attachments';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Attachments';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Attachment::FILE_SIZE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Attachment::MIME_TYPE,
			SearchFields_Attachment::FILE_SIZE,
			SearchFields_Attachment::MESSAGE_CREATED_DATE,
			SearchFields_Attachment::ADDRESS_EMAIL,
			SearchFields_Attachment::TICKET_MASK,
		);
		
//		$this->params = array(
//			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
//		);
	}

	function getData() {
		$objects = DAO_Attachment::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/configuration/tabs/attachments/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::FILEPATH:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
			case SearchFields_Attachment::ADDRESS_EMAIL:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_Attachment::ID:
//			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::FILE_SIZE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Attachment::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Attachment::ID]);
		unset($fields[SearchFields_Attachment::MESSAGE_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::FILEPATH:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
			case SearchFields_Attachment::ADDRESS_EMAIL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Attachment::ID:
			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::FILE_SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$deleted = false;

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'deleted':
					$deleted = true;
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Attachment::search(
				$this->params,
				100,
				$pg++,
				SearchFields_Attachment::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) { 
				DAO_Attachment::update($batch_ids, $change_fields);
			} else {
				DAO_Attachment::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};