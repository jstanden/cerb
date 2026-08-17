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

use Cerb\Records\SchemaBuilder;

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

	// The reflection itself lives in Cerb\Records\SchemaBuilder, shared with the agent terminal's
	// `cerb records` CLI so the two can't disagree about what a record type accepts. It returns notes as
	// TEXT; the only thing left to do here is render them as HTML for the reference tables.
	private function _buildRecordType($context_id, $mft, $custom_fields, $custom_fieldsets) : array {
		$rt = SchemaBuilder::describeType($context_id, $mft, $custom_fields, $custom_fieldsets);

		foreach($rt['search'] as &$row) {
			if(array_key_exists('notes', $row))
				$row['notes'] = $this->_noteToHtml($row['notes']);
		}
		unset($row);

		foreach($rt['api'] as &$row)
			$row['notes'] = $this->_noteToHtml($row['notes'] ?? '');
		unset($row);

		foreach($rt['params'] as &$param) {
			foreach($param['rows'] as &$param_row)
				$param_row['value'] = $this->_noteToHtml($param_row['value'] ?? '');
			unset($param_row);
		}
		unset($param);

		return $rt;
	}

	// Render a SchemaBuilder note as safe inline HTML: doc-site links are already flattened to their text
	// (we want the reference self-contained), so keep `code` spans and escape the rest.
	private function _noteToHtml(string $note) : string {
		if($note === '')
			return '';

		$html = DevblocksPlatform::strEscapeHtml(SchemaBuilder::noteToText($note));

		// `code` -> <code>code</code>
		$html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

		return $html;
	}

}
