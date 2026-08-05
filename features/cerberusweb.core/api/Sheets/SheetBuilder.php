<?php
namespace Cerb\Sheets;

/**
 * Descriptor source for the visual Sheet Builder (CerbUI.SheetBuilder).
 *
 * Mirrors the Form Builder's getFormComponentSchema() shape: each column type declares a {title, icon,
 * fields:[…], new} inspector descriptor; the client renders guided controls from `fields` and emits the
 * column KATA from the model. Curated "display" types get full field sets; the remaining action types
 * (interaction/search/search_button/toolbar) fall back to a raw-KATA `params` field on the client.
 *
 * This is the single source shared by the standalone Setup→Developers tool today and (deferred) the sheet
 * widgets + await:sheet elements. Callers narrow the offered set via allowedColumnTypes() /
 * allowedDataSourceTypes(), exactly as the runtime narrows the registered sheet column types per context.
 *
 * Field `input` values consumed by sheet-builder.js: text, multiline, number, bool, select, kata, and
 * `datakey` (a SelectMenu populated from the loaded sample dataset's keys, free-text fallback).
 */
class SheetBuilder {
	/**
	 * The shared per-cell presentation params every column type supports (resolved by
	 * _DevblocksSheetService::getRows()). Appended after each curated type's own fields.
	 */
	static function presentationFields() : array {
		return [
			// Colors reference a palette entry (`<name>:<index>`) — not a literal hex. See getLayoutSchema colors.
			['key' => 'color',      'label' => 'Background color', 'input' => 'paletteref'],
			['key' => 'text_color', 'label' => 'Text color',       'input' => 'paletteref'],
			['key' => 'text_size',  'label' => 'Text size',         'input' => 'text', 'placeholder' => 'e.g. 150%'],
			['key' => 'text_align', 'label' => 'Text align',        'input' => 'select', 'options' => ['', 'left', 'center', 'right']],
			['key' => 'bold',       'label' => 'Bold',              'input' => 'bool'],
		];
	}

	/**
	 * A mutually-exclusive value SOURCE — literal / key / template — rendered as one switcher group (the client
	 * shows a Literal/Key/Template switcher + the active input, and emits only the active source). Members share
	 * an `sgroup`; each carries an `srole`. `$literal=false` drops the literal role (e.g. a card's text).
	 */
	private static function _source(string $base, string $label, string $literalInput='text', bool $literal=true) : array {
		// Key first (the usual choice + the `(auto)` one), then Template, then the literal.
		$out = [];
		$out[] = ['key' => $base . '_key',      'label' => $label, 'input' => 'datakey',  'sgroup' => $base, 'srole' => 'key'];
		$out[] = ['key' => $base . '_template', 'label' => $label, 'input' => 'template', 'sgroup' => $base, 'srole' => 'template'];
		if($literal)
			$out[] = ['key' => $base,           'label' => $label, 'input' => $literalInput, 'sgroup' => $base, 'srole' => 'literal'];
		return $out;
	}

	/**
	 * The inline `icon:` source group (text/card/link) — a dynamic icon. Nested under a parent `icon:` object, so
	 * its emitted keys (image/image_key/image_template) don't collide with a sibling `image` param (card avatar).
	 * Model keys are icon/icon_key/icon_template; `snest`+`snestkey` map the active role to the nested param.
	 */
	private static function _iconSource() : array {
		return [
			['key' => 'icon_key',      'label' => 'Icon', 'input' => 'datakey',    'sgroup' => 'icon', 'srole' => 'key',      'snest' => 'icon', 'snestkey' => 'image_key'],
			['key' => 'icon_template', 'label' => 'Icon', 'input' => 'template',   'sgroup' => 'icon', 'srole' => 'template', 'snest' => 'icon', 'snestkey' => 'image_template'],
			['key' => 'icon',          'label' => 'Icon', 'input' => 'iconpicker', 'sgroup' => 'icon', 'srole' => 'literal',  'snest' => 'icon', 'snestkey' => 'image'],
		];
	}

	/**
	 * @return array {type => {title, icon, curated:bool, fields:[…], new:{…}}}
	 */
	static function getColumnSchema() : array {
		$pres = self::presentationFields();

		$schema = [
			'card' => [
				'title' => 'Card',
				'icon' => 'id-card',
				'curated' => true,
				// The key sources default to (auto): empty → the card inherits the column key (e.g. `group_id`).
				'fields' => array_merge(
					self::_source('context', 'Record type'),
					self::_source('id', 'Record ID'),
					self::_source('label', 'Card text', 'text', false),   // key/template only (literal is the column heading)
					self::_iconSource(),
					$pres,
					// Toggles grouped at the end next to Bold: Underline (default on → emits explicit no), then Show avatar.
					[
						['key' => 'underline', 'label' => 'Underline',   'input' => 'bool', 'default' => true],
						['key' => 'image',     'label' => 'Show avatar', 'input' => 'bool'],
					]
				),
				'new' => [],
			],
			'text' => [
				'title' => 'Text',
				'icon' => 'text',
				'curated' => true,
				'fields' => array_merge(
					self::_source('value', 'Value'),
					[['key' => 'value_map', 'label' => 'Value map', 'input' => 'kata']],
					self::_iconSource(),
					$pres
				),
				'new' => ['value_key' => ''],
			],
			'date' => [
				'title' => 'Date',
				'icon' => 'calendar',
				'curated' => true,
				'fields' => array_merge(
					self::_source('value', 'Value'),
					[['key' => 'format', 'label' => 'Format', 'input' => 'datechooser']],   // absolute by default; presets offered
					$pres
				),
				'new' => ['value_key' => ''],
			],
			'link' => [
				'title' => 'Link',
				'icon' => 'link',
				'curated' => true,
				'fields' => array_merge(
					self::_source('text', 'Text'),
					self::_source('href', 'URL'),
					[['key' => 'href_new_tab', 'label' => 'Open in new tab', 'input' => 'bool']],
					self::_iconSource(),
					$pres
				),
				'new' => ['text_key' => '_label', 'href_key' => 'record_url'],
			],
			'icon' => [
				'title' => 'Icon',
				'icon' => 'picture',
				'curated' => true,
				'fields' => array_merge(
					self::_source('image', 'Icon name', 'iconpicker'),
					[['key' => 'record_uri', 'label' => 'Record URI', 'input' => 'text']],
					$pres
				),
				'new' => ['image' => 'circle-ok'],
			],
			'markdown' => [
				'title' => 'Markdown',
				'icon' => 'file-document',
				'curated' => true,
				'fields' => array_merge(self::_source('value', 'Value'), $pres),
				'new' => ['value_key' => ''],
			],
			'selection' => [
				'title' => 'Selection',
				'icon' => 'checked',
				'curated' => true,
				'fields' => array_merge(
					[['key' => 'mode', 'label' => 'Mode', 'input' => 'select', 'options' => [['', 'Multiple (checkbox)'], ['single', 'Single (radio)']]]],
					self::_source('value', 'Value'),
					[['key' => 'selectable', 'label' => 'Selectable template', 'input' => 'kata']],
					$pres
				),
				'new' => ['value_key' => 'id'],
			],
			'slider' => [
				'title' => 'Slider',
				'icon' => 'slider',
				'curated' => true,
				'fields' => array_merge(
					[
						['key' => 'min', 'label' => 'Min', 'input' => 'number', 'default' => 0],
						['key' => 'max', 'label' => 'Max', 'input' => 'number', 'default' => 100],
					],
					self::_source('value', 'Value'),
					[['key' => 'show_labels', 'label' => 'Show labels', 'input' => 'bool']],
					$pres
				),
				'new' => ['min' => 0, 'max' => 100, 'value_key' => ''],
			],
			'time_elapsed' => [
				'title' => 'Time elapsed',
				'icon' => 'stopwatch',
				'curated' => true,
				'fields' => array_merge(
					self::_source('value', 'Value'),
					[['key' => 'precision', 'label' => 'Precision', 'input' => 'number', 'default' => 2]],
					$pres
				),
				'new' => ['value_key' => '', 'precision' => 2],
			],
			'code' => [
				'title' => 'Code',
				'icon' => 'embed',
				'curated' => true,
				'fields' => array_merge(
					self::_source('value', 'Value'),
					[['key' => 'syntax', 'label' => 'Syntax', 'input' => 'select', 'options' => ['', 'kata', 'json', 'diff']]],
					$pres
				),
				'new' => ['value_key' => ''],
			],

			// Action-ish types: raw-KATA params fallback (client renders a single `params` KATA field).
			'interaction' => [
				'title' => 'Interaction', 'icon' => 'zap', 'curated' => false, 'fields' => [],
				'new' => ['text' => 'Link text', 'uri' => 'cerb:automation:example.interaction.name'],
			],
			'search' => [
				'title' => 'Search', 'icon' => 'search', 'curated' => false, 'fields' => [],
				'new' => ['context' => 'ticket', 'query_template' => 'owner.id:{{id}}'],
			],
			'search_button' => [
				'title' => 'Search button', 'icon' => 'funnel', 'curated' => false, 'fields' => [],
				'new' => ['context' => 'ticket', 'query_template' => 'owner.id:{{id}}'],
			],
			'toolbar' => [
				'title' => 'Toolbar', 'icon' => 'hammer', 'curated' => false, 'fields' => [],
				'new' => [],
			],
		];

		return $schema;
	}

	/**
	 * Sheet-wide layout settings (getLayout()).
	 * @return array {fields:[…]}
	 */
	static function getLayoutSchema() : array {
		// `colors` is handled by a dedicated palette editor (not a generic field). Style uses a switcher.
		return [
			'fields' => [
				['key' => 'style',        'label' => 'Style',        'input' => 'switcher', 'options' => ['table', 'fieldsets', 'grid', 'columns'], 'default' => 'table'],
				['key' => 'headings',     'label' => 'Show headings','input' => 'bool', 'default' => true],
				['key' => 'paging',       'label' => 'Paging',       'input' => 'bool'],
				['key' => 'filtering',    'label' => 'Filtering',    'input' => 'bool'],
				['key' => 'title_column', 'label' => 'Title column',  'input' => 'columnkey'],
			],
		];
	}

	/**
	 * The three dataset authoring modes. Each mode's `fields` drive its editor panel.
	 * @return array {mode => {title, icon, fields:[…]}}
	 */
	static function getDataSourceSchema() : array {
		return [
			'records' => [
				'title' => 'Records',
				'icon' => 'collection',
				'fields' => [
					['key' => 'record_type', 'label' => 'Record type', 'input' => 'recordtype'],
					['key' => 'expand',      'label' => 'Expand keys', 'input' => 'text', 'placeholder' => 'e.g. customfields,owner_'],
					['key' => 'query',       'label' => 'Query',       'input' => 'searchquery'],
				],
			],
			'dataQuery' => [
				'title' => 'Data query',
				'icon' => 'database',
				'fields' => [
					// Any data query whose output is format:dictionaries.
					['key' => 'data_query', 'label' => 'Data query', 'input' => 'dataquery'],
				],
			],
			'automation' => [
				'title' => 'Automation',
				'icon' => 'zap',
				'fields' => [
					['key' => 'uri',    'label' => 'ui.sheet.data automation', 'input' => 'automationuri'],
					['key' => 'inputs', 'label' => 'Inputs', 'input' => 'kata'],
				],
			],
			'manual' => [
				'title' => 'Manual',
				'icon' => 'edit',
				'fields' => [
					['key' => 'rows', 'label' => 'Sample rows (JSON)', 'input' => 'json'],
				],
			],
		];
	}

	/** Default: every column type. Callers (contexts) narrow this. */
	static function allowedColumnTypes() : array {
		return array_keys(self::getColumnSchema());
	}

	/** Default: all three dataset modes. Callers narrow this. */
	static function allowedDataSourceTypes() : array {
		return array_keys(self::getDataSourceSchema());
	}

	/**
	 * Everything a `CerbUI.SheetBuilder` instance needs, as PHP structures (callers json_encode each). Shared by
	 * the standalone Setup→Developers page and the Form Builder popup so they can't drift. Pass a narrowed
	 * column-type set for a context that restricts them (e.g. the website interaction sheet).
	 * @return array {columnSchema, layoutSchema, dataSourceSchema, allowedColumnTypes, allowedDataSourceTypes, recordTypes, sheetDataAutomations}
	 */
	static function getClientConfig(?array $allowedColumnTypes=null) : array {
		// Record types (drives the dataQuery record-type picker + column datakey scoping)
		$record_types = [];
		foreach(\Extension_DevblocksContext::getAll(false) as $context_id => $mft) {
			$alias = $mft->params['alias'] ?? '';
			if(!$alias)
				continue;
			$record_types[] = [
				'context' => $context_id,
				'alias' => $alias,
				'label' => $mft->name,
				'icon' => $mft->params['icon'] ?? 'collection',
			];
		}
		usort($record_types, fn($a, $b) => strcasecmp($a['label'], $b['label']));

		// ui.sheet.data automations (drives the automation dataset-mode picker)
		$sheet_data_automations = [];
		$automations = \DAO_Automation::getWhere(sprintf("%s = %s",
			\Cerb_ORMHelper::escape(\DAO_Automation::EXTENSION_ID),
			\Cerb_ORMHelper::qstr(\AutomationTrigger_UiSheetData::ID)
		));
		foreach($automations as $automation) {
			$sheet_data_automations[] = ['id' => $automation->id, 'uri' => $automation->name, 'label' => $automation->name];
		}
		usort($sheet_data_automations, fn($a, $b) => strcasecmp($a['label'], $b['label']));

		return [
			'columnSchema' => self::getColumnSchema(),
			'layoutSchema' => self::getLayoutSchema(),
			'dataSourceSchema' => self::getDataSourceSchema(),
			'allowedColumnTypes' => $allowedColumnTypes ?? self::allowedColumnTypes(),
			'allowedDataSourceTypes' => self::allowedDataSourceTypes(),
			'recordTypes' => $record_types,
			'sheetDataAutomations' => $sheet_data_automations,
		];
	}
}
