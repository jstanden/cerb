{$peek_context = CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{$board = $model->getProjectBoard()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" data-cerb-popup-title="{'projects.common.board.column'|devblocks_translate|capitalize}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="project_board_column">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'projects.common.board'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="boardChooser_{$form_id}">
					{if $model}
						{if $board}
							<li data-context-id="{$board->id}" data-label="{$board->name}"></li>
						{/if}
					{/if}
				</div>
			</div>
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

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-editor-cards>
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Event: Render card <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
	</div>

	{$toolbar_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.eventHandlers.editor',

	'board__context' => CerberusContexts::CONTEXT_PROJECT_BOARD,
	'board_id' => $board->id,

	'board_column__context' => CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN,
	'board_column_id' => $peek_context_id,

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

	{if $trigger_render_card_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_render_card_ext->getEventPlaceholders()}
	{/if}
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-editor-functions>
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Event: Card added to column <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
	</div>

	{$toolbar_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.eventHandlers.editor',

	'board__context' => CerberusContexts::CONTEXT_PROJECT_BOARD,
	'board_id' => $board->id,

	'board_column__context' => CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN,
	'board_column_id' => $peek_context_id,

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

	<textarea name="functions_kata" data-editor-lines="15" spellcheck="false">{$model->functions_kata}</textarea>

	{if $trigger_card_action_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_card_action_ext->getEventPlaceholders()}
	{/if}
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-editor-toolbar>
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Toolbar <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
	</div>

	{$toolbar_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.editor',

	'board__context' => CerberusContexts::CONTEXT_PROJECT_BOARD,
	'board_id' => $board->id,

	'board_column__context' => CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN,
	'board_column_id' => $peek_context_id,

	'worker__context' => CerberusContexts::CONTEXT_WORKER,
	'worker_id' => $active_worker->id
	])}

	{$toolbar_kata =
"menu/insert:
  icon: circle-plus
  items:
    interaction/interaction:
      label: Interaction
      uri: ai.cerb.toolbarBuilder.interaction
    interaction/menu:
      label: Menu
      uri: ai.cerb.toolbarBuilder.menu
"}

	{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

	{* The editor toolbar is the KataEditor's integrated strip below; this hidden <ul> is its host section. *}
	<div data-cerb-toolbar-builder hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>

	<textarea name="toolbar_kata" data-editor-lines="15" spellcheck="false">{$model->toolbar_kata}</textarea>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="project board column"}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="cerb-ui-button create"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'projects.common.board.column'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.create').click({ mode: 'create' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#boardChooser_{$form_id}')[0], { context: '{CerberusContexts::CONTEXT_PROJECT_BOARD}', name: 'board_id', emptyIcon: 'kanban', searchPlaceholder: "{'projects.common.board'|devblocks_translate|capitalize|escape:'javascript' nofilter}" });

		// Cards — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test toggles)
		let $cards = $popup.find('[data-cerb-editor-cards]');

		let editor_cards = new CerbUI.KataEditor($cards.find('textarea[name=cards_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
			toolbar: {
				sections: [
					$cards.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
					$cards.find('[data-cerb-event-toolbar]')[0]
				],
				toolbarOpts: {
					caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
					width: '75%',
					start: function(formData) {
						let pos = editor_cards.getCursorPosition();
						formData.set('caller[params][selected_text]', editor_cards.getSelectedText());
						formData.set('caller[params][token_path]', editor_cards.getTokenPath().join(''));
						formData.set('caller[params][cursor_row]', pos.row);
						formData.set('caller[params][cursor_column]', pos.column);
						formData.set('caller[params][trigger]', 'cerb.trigger.projectBoard.renderCard');
						formData.set('caller[params][value]', editor_cards.getValue());
					},
					done: function(e) {
						e.stopPropagation();
						if(!e.trigger.is('.cerb-bot-trigger'))
							return;
						if(e.eventData.exit === 'return')
							Devblocks.interactionWorkerPostActions(e.eventData, editor_cards);
					}
				},
				onAction: function(value, ed, item) {
					if(value === 'placeholders') { $cards.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
					if(value === 'tester')       { $cards.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
					return false;
				}
			}
		});

		CerbUI.editorCore.attachEventHandlerTester($cards, editor_cards);

		// Functions — KataEditor with integrated event-handler toolbar
		let $functions = $popup.find('[data-cerb-editor-functions]');

		let editor_functions = new CerbUI.KataEditor($functions.find('textarea[name=functions_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
			toolbar: {
				sections: [
					$functions.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
					$functions.find('[data-cerb-event-toolbar]')[0]
				],
				toolbarOpts: {
					caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
					width: '75%',
					start: function(formData) {
						let pos = editor_functions.getCursorPosition();
						formData.set('caller[params][selected_text]', editor_functions.getSelectedText());
						formData.set('caller[params][token_path]', editor_functions.getTokenPath().join(''));
						formData.set('caller[params][cursor_row]', pos.row);
						formData.set('caller[params][cursor_column]', pos.column);
						formData.set('caller[params][trigger]', 'cerb.trigger.projectBoard.cardAction');
						formData.set('caller[params][value]', editor_functions.getValue());
					},
					done: function(e) {
						e.stopPropagation();
						if(!e.trigger.is('.cerb-bot-trigger'))
							return;
						if(e.eventData.exit === 'return')
							Devblocks.interactionWorkerPostActions(e.eventData, editor_functions);
					}
				},
				onAction: function(value, ed, item) {
					if(value === 'placeholders') { $functions.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
					if(value === 'tester')       { $functions.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
					return false;
				}
			}
		});

		CerbUI.editorCore.attachEventHandlerTester($functions, editor_functions);

		// Toolbar — KataEditor with the toolbar-builder insert menu merged in as a host section
		let $toolbar = $popup.find('[data-cerb-editor-toolbar]');

		let editor_toolbar = new CerbUI.KataEditor($toolbar.find('textarea[name=toolbar_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataToolbar),
			toolbar: {
				sections: [
					$toolbar.find('[data-cerb-toolbar-builder] ul.cerb-ui-toolbar')[0]
				],
				toolbarOpts: {
					caller: { name: 'cerb.toolbar.editor', params: { toolbar: 'cerb.toolbar.projectBoardColumn', selected_text: '' } },
					start: function(formData) {
						formData.set('caller[params][selected_text]', editor_toolbar.getSelectedText());
					},
					done: function(e) {
						e.stopPropagation();
						if(!e.trigger.is('.cerb-bot-trigger'))
							return;
						if(e.eventData.exit === 'return')
							Devblocks.interactionWorkerPostActions(e.eventData, editor_toolbar);
					}
				}
			}
		});

		$popup.find('input[name=name]').focus();

	});
});
</script>
