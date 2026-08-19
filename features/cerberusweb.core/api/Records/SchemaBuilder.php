<?php
namespace Cerb\Records;

use C4_AbstractView;
use DAO_CustomField;
use DAO_CustomFieldset;
use DevblocksExtensionManifest;
use DevblocksPlatform;
use DevblocksSearchCriteria;
use Extension_DevblocksContext;
use Model_CustomField;
use Throwable;

/**
 * Reflects a record type into the two key sets people actually ask about: what you can SEARCH it by
 * (quick-search filter keys) and what you can WRITE to it (the records-API keys behind
 * `record.create` / `record.update`), plus its custom fields and fieldsets.
 *
 * Shared rather than owned by one caller: Setup > Records renders this as HTML tables, and the agent
 * terminal's `cerb records` CLI renders the same arrays as markdown. Forking it would let the two
 * disagree, which shows up as an agent being told a filter key exists that the Setup page says does not.
 *
 * Notes come back as TEXT (doc-site links flattened, backticks intact) so each caller picks its own
 * presentation -- see noteToText().
 */
class SchemaBuilder {
	/**
	 * Everything about one record type. `$custom_fields` / `$custom_fieldsets` are passed in rather than
	 * fetched so a caller describing many types pays for one `getAll()` instead of one per type.
	 */
	static function describeType(string $context_id, DevblocksExtensionManifest $mft, ?array $custom_fields=null, ?array $custom_fieldsets=null) : array {
		if(is_null($custom_fields))
			$custom_fields = DAO_CustomField::getAll();

		if(is_null($custom_fieldsets))
			$custom_fieldsets = DAO_CustomFieldset::getAll();

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
					'notes' => self::_customFieldNotes($field),
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
					'notes' => self::noteToText($collapse_notes[$prefix]),
				];
			}
		}

		// Records API field keys — what record.create / automations accept (DAO columns)
		[$api, $params] = self::apiKeys($context_id, $mft);

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

	/**
	 * What a custom field's TYPE alone doesn't tell you.
	 *
	 * "Record Link" says the shape and not the target, and "Picklist" says there is a fixed set without
	 * saying what's in it -- so a caller reading this schema can't write a value or a filter for either one
	 * without guessing. Both answers are already sitting in the field's `params`; they were just never
	 * surfaced.
	 *
	 * Returns '' for the types that need no explanation, so a caller can skip empty notes.
	 */
	private static function _customFieldNotes(Model_CustomField $field) : string {
		$params = is_array($field->params) ? $field->params : [];

		// `context` is the linked record type -- set by the built-in Record Link type and by the Record Links
		// (multiple) type extension alike. Reported as the ALIAS, since that's what every other part of this
		// schema speaks and what a search query or a `record.create` would use.
		if('' !== ($context_id = trim(strval($params['context'] ?? '')))) {
			$alias = $context_id;

			// Manifest only (`false`) -- resolving an alias must not instantiate a context, which would drag in
			// its DAO for every linked field on every type.
			if(($linked_mft = Extension_DevblocksContext::get($context_id, false)))
				$alias = trim(strval($linked_mft->params['alias'] ?? '')) ?: $context_id;

			return sprintf('Links to `%s` records.', $alias);
		}

		// Picklist / Multiple Checkboxes: the valid values ARE the contract.
		if(is_array($options = $params['options'] ?? []) && $options) {
			$options = array_values(array_filter(array_map('strval', $options), fn($o) => '' !== trim($o)));

			if(!$options)
				return '';

			// A long picklist would otherwise dominate the table it's described in. The cap is generous enough
			// that most fields print in full, and the overflow count tells a reader the list continues.
			$shown = array_slice($options, 0, 20);

			return sprintf('One of: %s%s',
				implode(', ', array_map(fn($o) => sprintf('`%s`', $o), $shown)),
				count($options) > count($shown) ? sprintf(' (+%d more)', count($options) - count($shown)) : ''
			);
		}

		return '';
	}

	/**
	 * The writable "records API" keys (record.create / record.update / automations) for a record type.
	 * These are the DAO columns the context exposes via getKeyMeta() — the keys you use in `fields:`.
	 * Returns [$keys, $params] where $params holds any `_reference` sub-schemas (e.g. a draft's
	 * `params (mail.compose)`), so the whole record reference is self-contained here.
	 *
	 * This is the one part that INSTANTIATES the context, so call it per type on demand -- instantiating
	 * every context requires every DAO file in the app.
	 */
	static function apiKeys(string $context_id, DevblocksExtensionManifest $mft) : array {
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
				'notes' => self::noteToText($meta['notes'] ?? ''),
			];

			// Object fields (e.g. a draft's `params`) can carry sub-schemas under `_reference` — surface
			// each as its own keyed reference table below the main keys.
			if(!empty($meta['_reference']) && is_array($meta['_reference'])) {
				foreach($meta['_reference'] as $ref_title => $ref_rows) {
					if(!is_array($ref_rows))
						continue;

					$rows = [];
					foreach($ref_rows as $ref_key => $ref_desc)
						$rows[] = ['key' => $ref_key, 'value' => self::noteToText((string)$ref_desc)];

					$params[] = ['title' => $ref_title, 'rows' => $rows];
				}
			}
		}

		return [$api, $params];
	}

	/**
	 * Flatten a getKeyMeta() markdown note to self-contained text: drop doc-site links to their text,
	 * keep `code` spans as backticks. Callers escape or render from here -- Setup wraps the backticks in
	 * <code>, the CLI emits them as markdown.
	 */
	static function noteToText(string $note) : string {
		if($note === '')
			return '';

		// [text](/url) -> text
		return preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $note);
	}
}
