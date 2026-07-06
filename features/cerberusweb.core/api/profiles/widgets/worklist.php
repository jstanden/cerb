<?php
class ProfileWidget_Worklist extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.worklist';
	
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$view_context = $model->extension_params['context'] ?? null;
		$query = $model->extension_params['query'] ?? null;
		$query_required = $model->extension_params['query_required'] ?? null;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Unique instance per widget/record combo
		$view_id = sprintf('profile_widget_%d_%d', $model->id, $context_id);
		
		if(!$view_context || !($view_context_ext = Extension_DevblocksContext::get($view_context)))
			return;
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$defaults = C4_AbstractViewModel::loadFromClass($view_context_ext->getViewClass());
			$defaults->id = $view_id;
			$defaults->is_ephemeral = true;
			$defaults->options = [];
			$defaults->name = ' ';
			$defaults->paramsEditable = [];
			$defaults->paramsDefault = [];
			$defaults->view_columns = $model->extension_params['columns'];
			$defaults->options['header_color'] = @$model->extension_params['header_color'] ?: '#626c70';
			$defaults->renderLimit = DevblocksPlatform::intClamp(@$model->extension_params['render_limit'], 1, 50);
			
			if(!($view = C4_AbstractViewLoader::unserializeAbstractView($defaults, false)))
				return;
		}
		
		$view->renderPage = 0;
		
		// [TODO] Adding params this way invalidates the view checksum
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		if($query_required) {
			$query_required = $tpl_builder->build($query_required, $dict);
		}
		
		$view->addParamsRequiredWithQuickSearch($query_required);
		
		if($query) {
			$query = $tpl_builder->build($query, $dict);
		}
		
		$view->setParamsQuery($query);
		$view->addParamsWithQuickSearch($query);
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);

		$context_mfts = Extension_DevblocksContext::getAll(false, ['workspace']);
		$tpl->assign('context_mfts', $context_mfts);

		$context = $model->extension_params['context'] ?? null;
		$columns = $model->extension_params['columns'] ?? [];

		$grouped = ['base_label' => '', 'base' => [], 'fieldsets' => []];

		if($context)
			$grouped = self::getContextColumnsGrouped($context, $columns);

		$tpl->assign('columns_json', json_encode($grouped));

		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/worklist/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
	
	function saveConfig(array $fields, $id=null, &$error=null) {
		if($id) {
			// Remove worker view models
			$view_id = sprintf('profile_widget_%d_', $id);
			DAO_WorkerViewModel::deleteByViewIdPrefix($view_id);
		}
		
		return true;
	}
	
	/**
	 * Build the available columns for a record type, segregating any custom-field columns into their
	 * custom fieldset. Returns `['base_label', 'base' => [...], 'fieldsets' => [['id','name','columns'],...]]`.
	 * Every column posts to the flat `params[columns][]` list regardless of group — grouping is display-only.
	 */
	public static function getContextColumnsGrouped($context, $columns_selected=[]) {
		$grouped = ['base_label' => '', 'base' => [], 'fieldsets' => []];

		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return $grouped;

		$grouped['base_label'] = $context_ext->manifest->name;

		$view_class = $context_ext->getViewClass();

		if(empty($view_class) || null == ($view = new $view_class())) /* @var $view C4_AbstractView */
			return $grouped;

		$view->setAutoPersist(false);

		$columns_avail = $view->getColumnsAvailable();

		if(empty($columns_selected))
			$columns_selected = $view->view_columns;

		// Map custom-field columns (token `cf_<id>`) to their fieldset so we can segregate them below
		$custom_fields = DAO_CustomField::getByContext($context_ext->id, true, false);
		$custom_fieldsets = DAO_CustomFieldset::getAll();

		$fieldsets = [];

		if(is_array($columns_avail))
		foreach($columns_avail as $column) {
			if(empty($column->db_label))
				continue;

			$entry = [
				'key' => $column->token,
				'label' => mb_convert_case($column->db_label, MB_CASE_TITLE),
				'type' => $column->type,
				'is_selected' => in_array($column->token, $columns_selected),
			];

			$fieldset_id = 0;

			if(DevblocksPlatform::strStartsWith($column->token, 'cf_')) {
				$field_id = substr($column->token, 3);
				$cfield = $custom_fields[$field_id] ?? null;

				if($cfield && !empty($cfield->custom_fieldset_id) && isset($custom_fieldsets[$cfield->custom_fieldset_id]))
					$fieldset_id = $cfield->custom_fieldset_id;
			}

			if($fieldset_id) {
				if(!isset($fieldsets[$fieldset_id]))
					$fieldsets[$fieldset_id] = [
						'id' => $fieldset_id,
						'name' => $custom_fieldsets[$fieldset_id]->name,
						'columns' => [],
					];

				$fieldsets[$fieldset_id]['columns'][] = $entry;

			} else {
				$grouped['base'][] = $entry;
			}
		}

		// Selected-first (in saved order), then alphabetical — applied per group
		self::_sortContextColumns($grouped['base'], $columns_selected);

		foreach($fieldsets as &$fieldset)
			self::_sortContextColumns($fieldset['columns'], $columns_selected);
		unset($fieldset);

		// Stable group order by fieldset name
		usort($fieldsets, fn($a, $b) => strcmp($a['name'], $b['name']));

		$grouped['fieldsets'] = array_values($fieldsets);

		return $grouped;
	}

	private static function _sortContextColumns(array &$columns, array $columns_selected) : void {
		usort($columns, function($a, $b) use ($columns_selected) {
			if($a['is_selected'] == $b['is_selected']) {
				if($a['is_selected']) {
					$a_idx = array_search($a['key'], $columns_selected);
					$b_idx = array_search($b['key'], $columns_selected);
					return $a_idx < $b_idx ? -1 : 1;

				} else {
					return strcmp($a['label'], $b['label']);
				}
			}

			return $a['is_selected'] ? -1 : 1;
		});
	}
}