<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class PageSection_SetupRecords extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'records');

		$custom_fields = DAO_CustomField::getAll();
		$custom_fieldsets = DAO_CustomFieldset::getAll();

		// All record types that support custom fields, alphabetized by label
		$contexts = Extension_DevblocksContext::getAll(false, 'custom_fields');
		uasort($contexts, fn($a, $b) => strcasecmp($a->name, $b->name));

		$record_types = [];
		foreach($contexts as $context_id => $mft)
			$record_types[] = $this->_buildRecordType($context_id, $mft, $custom_fields, $custom_fieldsets);

		$tpl->assign('record_types', $record_types);
		$this->_assignContexts($tpl);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/records/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch($action) {
				case 'renderSectionBody':
					return $this->_configAction_renderSectionBody();
			}
		}
		return false;
	}

	// Re-render a single record type's body (for incremental refresh after a custom field/fieldset change)
	private function _configAction_renderSectionBody() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', '');

		$contexts = Extension_DevblocksContext::getAll(false);

		if(false == ($mft = $contexts[$context] ?? null))
			return;

		$rt = $this->_buildRecordType(
			$context,
			$mft,
			DAO_CustomField::getAll(),
			DAO_CustomFieldset::getAll()
		);

		$tpl->assign('rt', $rt);
		$this->_assignContexts($tpl);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/records/_record_body.tpl');
	}

	private function _assignContexts($tpl) {
		$tpl->assign('context_custom_record', CerberusContexts::CONTEXT_CUSTOM_RECORD);
		$tpl->assign('context_custom_field', CerberusContexts::CONTEXT_CUSTOM_FIELD);
		$tpl->assign('context_custom_fieldset', CerberusContexts::CONTEXT_CUSTOM_FIELDSET);
	}

	private function _buildRecordType($context_id, $mft, $custom_fields, $custom_fieldsets) : array {
		$search = [];    // quick-search filter keys (worklists / record.search)
		$custom = [];    // custom fields not in a fieldset
		$fieldsets = []; // [fieldset_id => ['id','name','fields'=>[]]]

		// Seed every fieldset for this record type (so empty ones still show)
		foreach($custom_fieldsets as $fsid => $fs) {
			if($fs->context === $context_id)
				$fieldsets[$fsid] = ['id' => $fsid, 'name' => $fs->name, 'fields' => []];
		}

		$view_class = $mft->params['view_class'] ?? null;

		if($view_class && class_exists($view_class)) {
			try {
				$view = new $view_class();
			} catch(Throwable) {
				$view = null;
			}

			// Custom fields come from the worklist columns (cf_ tokens) — they carry fieldset grouping
			try {
				$columns = $view ? $view->getColumnsAvailable() : [];
			} catch(Throwable) {
				$columns = [];
			}

			// Descriptive custom-field type labels ('Text: Single Line', …), incl. type extensions
			$cf_type_labels = Model_CustomField::getTypes();

			foreach($columns as $token => $col) {
				if(!$token || !$col->db_label)
					continue;
				if(!DevblocksPlatform::strStartsWith($token, 'cf_'))
					continue;

				$field_id = intval(substr($token, 3));

				if(false == ($field = $custom_fields[$field_id] ?? null))
					continue;

				[$icon, $color] = C4_AbstractView::getColumnDisplayMeta($field->type);
				$row = [
					'id' => $field_id,
					'label' => $field->name,
					'key' => $field->uri,
					'type' => $cf_type_labels[$field->type] ?? ucwords((string)$field->type),
					'icon' => $icon,
					'color' => $color,
				];

				if($field->custom_fieldset_id && isset($fieldsets[$field->custom_fieldset_id]))
					$fieldsets[$field->custom_fieldset_id]['fields'][] = $row;
				else
					$custom[] = $row;
			}

			// Quick-search filter keys — the per-record search filters (worklists / record.search)
			try {
				$quick_search = $view ? $view->getQuickSearchFields() : [];
			} catch(Throwable) {
				$quick_search = [];
			}

			// Per-record-type link families explode the list (one key per linkable alias). Collapse each
			// into a single representative `<prefix>.<record-type>` row.
			$collapse_notes = [
				'links' => 'A linked record — replace with any record type alias',
				'on' => 'The record this is on — replace with any record type alias',
				'author' => 'The author record — replace with any record type alias',
			];
			$collapsed_seen = [];

			foreach($quick_search as $key => $qf) {
				$type = $qf['type'] ?? '';

				if('hidden' === $type)
					continue;

				// Fold on.<type>: / links.<type>: / author.<type>: into one row apiece
				$collapse_prefix = null;
				foreach(array_keys($collapse_notes) as $prefix) {
					if(DevblocksPlatform::strStartsWith($key, $prefix . '.')) {
						$collapse_prefix = $prefix;
						break;
					}
				}
				if($collapse_prefix) {
					$collapsed_seen[$collapse_prefix] = true;
					continue;
				}

				[$icon, $color, $label] = C4_AbstractView::getColumnDisplayMeta($type);
				$search[] = [
					'key' => $key,
					'type' => $label ?: ucwords((string)$type),
					'icon' => $icon,
					'color' => $color,
				];
			}

			// Emit one representative row for each collapsed family that appeared
			[$link_icon, $link_color] = C4_AbstractView::getColumnDisplayMeta(DevblocksSearchCriteria::TYPE_CONTEXT);
			foreach($collapsed_seen as $prefix => $_) {
				$search[] = [
					'key' => $prefix . '.<record-type>',
					'type' => 'Record',
					'icon' => $link_icon,
					'color' => $link_color,
					'notes' => $this->_noteToHtml($collapse_notes[$prefix]),
				];
			}
		}

		// Records API field keys — what record.create / automations accept (DAO columns)
		[$api, $params] = $this->_buildApiKeys($context_id, $mft);

		usort($api, fn($a, $b) => strcasecmp($a['key'], $b['key']));
		usort($search, fn($a, $b) => strcasecmp($a['key'], $b['key']));
		usort($custom, fn($a, $b) => strcasecmp($a['label'], $b['label']));
		foreach($fieldsets as &$fs)
			usort($fs['fields'], fn($a, $b) => strcasecmp($a['label'], $b['label']));
		unset($fs);
		uasort($fieldsets, fn($a, $b) => strcasecmp($a['name'], $b['name']));

		$alias = $mft->params['alias'] ?? '';

		return [
			'id' => $context_id,
			'name' => $mft->name,
			'icon' => $mft->params['icon'] ?? 'collection',
			'uri' => $alias,
			'slug' => $alias ?: ('ctx-' . str_replace(['.', '_'], '-', $context_id)),
			'is_custom' => DevblocksPlatform::strStartsWith($context_id, 'contexts.custom_record.'),
			'record_id' => DevblocksPlatform::strStartsWith($context_id, 'contexts.custom_record.') ? intval(substr($context_id, strlen('contexts.custom_record.'))) : 0,
			'api' => $api,
			'params' => $params,
			'search' => $search,
			'custom' => $custom,
			'fieldsets' => array_values($fieldsets),
		];
	}

	// The writable "records API" keys (record.create / record.update / automations) for a record type.
	// These are the DAO columns the context exposes via getKeyMeta() — the keys you use in `fields:`.
	// Returns [$keys, $params] where $params holds any `_reference` sub-schemas (e.g. a draft's
	// `params (mail.compose)`), so the whole record reference is self-contained here.
	private function _buildApiKeys($context_id, $mft) : array {
		$api = [];
		$params = [];

		// Only record types that support the records API have writable keys
		if(!$mft->hasOption('records'))
			return [$api, $params];

		if(false == ($context_ext = Extension_DevblocksContext::get($context_id, true)))
			return [$api, $params];

		$dao_class = $context_ext->getDaoClass();

		if(!$dao_class || !method_exists($dao_class, 'create') || !method_exists($dao_class, 'getFields'))
			return [$api, $params];

		try {
			$key_meta = $context_ext->getKeyMeta(false);
		} catch(Throwable) {
			return [$api, $params];
		}

		foreach($key_meta as $key => $meta) {
			$type = $meta['type'] ?? '';
			[$icon, $color, $label] = C4_AbstractView::getColumnDisplayMeta($type);

			$api[] = [
				'key' => $key,
				'type' => $label ?: ucwords((string)$type),
				'icon' => $icon,
				'color' => $color,
				'required' => !empty($meta['is_required']),
				'notes' => $this->_noteToHtml($meta['notes'] ?? ''),
			];

			// Object fields (e.g. a draft's `params`) can carry sub-schemas under `_reference` — surface
			// each as its own keyed reference table below the main keys.
			if(!empty($meta['_reference']) && is_array($meta['_reference'])) {
				foreach($meta['_reference'] as $ref_title => $ref_rows) {
					if(!is_array($ref_rows))
						continue;

					$rows = [];
					foreach($ref_rows as $ref_key => $ref_desc)
						$rows[] = ['key' => $ref_key, 'value' => $this->_noteToHtml((string)$ref_desc)];

					$params[] = ['title' => $ref_title, 'rows' => $rows];
				}
			}
		}

		return [$api, $params];
	}

	// Turn a getKeyMeta() markdown note into safe inline HTML for the reference tables: drop doc-site
	// links to their text (we want the reference self-contained), keep `code` spans, escape the rest.
	private function _noteToHtml(string $note) : string {
		if($note === '')
			return '';

		// [text](/url) -> text
		$note = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $note);

		$html = DevblocksPlatform::strEscapeHtml($note);

		// `code` -> <code>code</code>
		$html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

		return $html;
	}

}
