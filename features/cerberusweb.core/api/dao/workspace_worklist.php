<?php
class DAO_WorkspaceList extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const ID = 'id';
	const LIST_POS = 'list_pos';
	const LIST_VIEW = 'list_view';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::CONTEXT)
			->context()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// smallint(5) unsigned
		$validation
			->addField(self::LIST_POS)
			->uint(2)
			;
		// mediumtext
		$validation
			->addField(self::LIST_VIEW)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKSPACE_TAB_ID)
			->id()
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO workspace_list () ".
			"VALUES ()"
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_WorkspaceList
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	/**
	 *
	 * @param string $where
	 * @return Model_WorkspaceList[]
	 */
	static function getWhere($where) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, workspace_tab_id, context, list_view, list_pos ".
			"FROM workspace_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : " ").
			"ORDER BY list_pos ASC";
		
		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;

		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WorkspaceList();
			$object->id = intval($row['id']);
			$object->workspace_tab_id = intval($row['workspace_tab_id']);
			$object->context = $row['context'];
			$object->list_pos = intval($row['list_pos']);
			
			$list_view = $row['list_view'];
			if(!empty($list_view)) {
				@$object->list_view = unserialize($list_view);
			}
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function getByTab($tab_id) {
		return DAO_WorkspaceList::getWhere(sprintf("%s = %d",
			DAO_WorkspaceList::WORKSPACE_TAB_ID,
			$tab_id,
			DAO_WorkspaceList::LIST_POS
		));
	}
	
	static function update($ids, $fields) {
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		self::_updateAbstract($context, $ids, $fields);
		
		parent::_update($ids, 'workspace_list', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_list', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, $fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::WORKSPACE_TAB_ID])) {
			$error = "A 'workspace_tab_id' is required.";
			return false;
		}
		
		if(isset($fields[self::WORKSPACE_TAB_ID])) {
			@$tab_id = $fields[self::WORKSPACE_TAB_ID];
			
			if(!$tab_id) {
				$error = "Invalid 'workspace_tab_id' value.";
				return false;
			}
			
			if(!Context_WorkspaceTab::isWriteableByActor($tab_id, $actor)) {
				$error = "You do not have permission to create worklists on this workspace tab.";
				return false;
			}
		}
		
		return true;
	}
	
	static function random() {
		return self::_getRandom('workspace_list');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::services()->database();
		$ids_list = implode(',', $ids);
		
		if(false == ($db->ExecuteMaster(sprintf("DELETE FROM workspace_list WHERE id IN (%s)", $ids_list))))
			return false;
		
		// Delete worker view prefs
		foreach($ids as $id) {
			$db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE view_id = 'cust_%d'", $id));
		}
	}
};



class Model_WorkspaceList {
	public $id = 0;
	public $workspace_tab_id = 0;
	public $context = '';
	public $list_view = '';
	public $list_pos = 0;
};

class Model_WorkspaceListView {
	public $title = 'New List';
	public $options = array();
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
	public $params_required = array();
	public $sort_by = null;
	public $sort_asc = 1;
	public $subtotals = '';
};

class Context_WorkspaceWorklist extends Extension_DevblocksContext {
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $models, 'tab_page_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $models, 'tab_page_owner_');
	}
	
	function getRandom() {
		return DAO_WorkspaceList::random();
	}
	
	function getDaoClass() {
		return 'DAO_WorkspaceList';
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

		if(null == ($workspace_list = DAO_WorkspaceList::get($context_id)))
			return array();
		
		return array(
			'id' => $workspace_list->id,
			'name' => 'Worklist', //$workspace_list->label, // [TODO]
			'permalink' => null,
			'updated' => 0, //$workspace_list->updated, // [TODO]
		);
	}
	
	function getContext($worklist, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workspace Worklist:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST);
		
		// Polymorph
		if(is_numeric($worklist)) {
			$worklist = DAO_WorkspaceList::get($worklist);
		} elseif($worklist instanceof Model_WorkspaceList) {
			// It's what we want already.
		} elseif(is_array($worklist)) {
			$worklist = Cerb_ORMHelper::recastArrayToModel($worklist, 'Model_WorkspaceWorklist');
		} else {
			$worklist = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'context' => $prefix.$translate->_('common.context'),
			'id' => $prefix.$translate->_('common.id'),
			'pos' => $prefix.$translate->_('common.pos'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'pos' => Model_CustomField::TYPE_NUMBER,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $worklist) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = 'Worklist'; //$worklist->name;
			$token_values['context'] = $worklist->context;
			$token_values['id'] = $worklist->id;
			$token_values['pos'] = $worklist->list_pos;
			$token_values['tab_id'] = $worklist->workspace_tab_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($worklist, $token_values);
		}
		
		// Tab
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_TAB, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'tab_',
			$prefix.'Tab:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'context' => DAO_WorkspaceList::CONTEXT,
			'id' => DAO_WorkspaceList::ID,
			'links' => '_links',
			'pos' => DAO_WorkspaceList::LIST_POS,
			'tab_id' => DAO_WorkspaceList::WORKSPACE_TAB_ID,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'links':
				$this->_getDaoFieldsLinks($value, $out_fields, $error);
				break;
			
			case 'view':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(!isset($value['title'])) {
					$error = "is missing the 'title' key.";
					return false;
				}
				
				if(!isset($value['model'])) {
					$error = "is missing the 'model' key.";
					return false;
				}
				
				if(false == (@$view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($value['model'], ''))) {
					$error = 'is not a valid worklist.';
					return false;
				}
				
				// Build the list model
				$list = new Model_WorkspaceListView();
				$list->title = $value['title'];
				$list->options = $view->options;
				$list->columns = $view->view_columns;
				$list->params = $view->getEditableParams();
				$list->params_required = $view->getParamsRequired();
				$list->num_rows = $view->renderLimit;
				$list->sort_by = $view->renderSortBy;
				$list->sort_asc = $view->renderSortAsc;
				$list->subtotals = $view->renderSubtotals;

				// [TODO] Nasty serialization
				$out_fields[DAO_WorkspaceList::LIST_VIEW] = serialize($list);
				break;
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, false);
		}
		
		switch($token) {
			/*
			case 'data':
				$values = $dictionary;
				
				if(null == ($widget = DAO_WorkspaceWidget::get($context_id)))
					break;
				
				$widget_ext = Extension_WorkspaceWidget::get($dictionary['extension_id']);

				$values['data'] = false;
				
				if(!($widget_ext instanceof ICerbWorkspaceWidget_ExportData))
					break;

				$json = json_decode($widget_ext->exportData($widget, 'json'), true);

				if(!is_array($json))
					break;
				
				// Remove redundant data
				if(isset($json['widget'])) {
					unset($json['widget']['label']);
					unset($json['widget']['version']);
				}
				
				$values['data'] = isset($json['widget']) ? $json['widget'] : $json;
				break;
			*/
				
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Worklists';
		//$view->renderSortBy = SearchFields_WorkspaceList::ID; // [TODO]
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Worklists';
		
		$params_req = array();
		
		/*
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_WorkspaceList::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_WorkspaceList::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		*/
		
		$view->renderTemplate = 'context';
		return $view;
	}
};