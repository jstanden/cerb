{$peek_context = CerberusContexts::CONTEXT_PROJECT_BOARD}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="project_board">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-tabs">
	{if !$model->id && $packages}
	<ul>
		<li><a href="#board-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#board-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="board-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="board-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
			</div>
		</div>
		{/if}

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

		<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-editor-cards>
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Event: Render card <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
			</div>

			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.eventHandlers.editor',

			'board__context' => $peek_context,
			'board_id' => $peek_context_id,

			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
			])}

			{$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  icon: circle-plus
  tooltip: Automation
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections. *}
			<div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_toolbar.tpl"}

			<textarea name="cards_kata" data-editor-lines="15" spellcheck="false">{$model->cards_kata}</textarea>
			
			{if $trigger_ext}
				{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
			{/if}
		</div>

		{if !empty($model->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="project board"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			{if $model->id}
				<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
			{else}
				<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);
	
	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'projects.common.board'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		let $fieldset_cards = $popup.find('[data-cerb-editor-cards]');

		// Buttons

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Editor — KataEditor with its integrated toolbar (Automation interaction + Placeholders/Test toggles as
		// host `sections`); interactions fire via `toolbarOpts`, the toggles route through `onAction`.
		let cards_editor = new CerbUI.KataEditor($fieldset_cards.find('textarea[name=cards_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
			toolbar: {
				sections: [
					$fieldset_cards.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
					$fieldset_cards.find('[data-cerb-event-toolbar]')[0]
				],
				toolbarOpts: {
					caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
					width: '75%',
					start: function(formData) {
						let pos = cards_editor.getCursorPosition();
						formData.set('caller[params][selected_text]', cards_editor.getSelectedText());
						formData.set('caller[params][token_path]', cards_editor.getTokenPath().join(''));
						formData.set('caller[params][cursor_row]', pos.row);
						formData.set('caller[params][cursor_column]', pos.column);
						formData.set('caller[params][trigger]', 'cerb.trigger.projectBoard.renderCard');
						formData.set('caller[params][value]', cards_editor.getValue());
					},
					done: function(e) {
						e.stopPropagation();
						if(!e.trigger.is('.cerb-bot-trigger'))
							return;
						if(e.eventData.exit === 'return')
							Devblocks.interactionWorkerPostActions(e.eventData, cards_editor);
					}
				},
				onAction: function(value, ed, item) {
					if(value === 'placeholders') { $fieldset_cards.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
					if(value === 'tester')       { $fieldset_cards.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
					return false;
				}
			}
		});

		CerbUI.editorCore.attachEventHandlerTester($fieldset_cards, cards_editor);

		// Package Library
		
		{if !$model->id && $packages}
			let $library_container = $popup.find('.cerb-tabs');
			$library_container.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function() {
				$popup.one('peek_saved peek_error', function() {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});
				
				$popup.find('button.save').click();
			});
		{/if}
		
	});
});
</script>
