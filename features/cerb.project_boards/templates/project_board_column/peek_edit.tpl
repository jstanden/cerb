{$peek_context = Context_ProjectBoardColumn::ID}
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

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'projects.common.board'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="board_id" data-context="{Context_ProjectBoard::ID}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{if $board}
						<li><input type="hidden" name="board_id" value="{$board->id}"><a class="cerb-peek-trigger no-underline" data-context="{Context_ProjectBoard::ID}" data-context-id="{$board->id}">{$board->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<fieldset class="peek" data-cerb-editor-cards>
	<legend>Event: Render card (KATA)</legend>

	<div class="cerb-code-editor-toolbar">
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
		'caller_name' => 'cerb.toolbar.eventHandlers.editor',
		
		'board__context' => Context_ProjectBoard::ID,
		'board_id' => $board->id,
		
		'board_column__context' => Context_ProjectBoardColumn::ID,
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

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>

	<textarea name="cards_kata" data-editor-mode="ace/mode/cerb_kata">{$model->cards_kata}</textarea>

	{if $trigger_render_card_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_render_card_ext->getEventPlaceholders()}
	{/if}
</fieldset>

<fieldset class="peek" data-cerb-editor-functions>
	<legend>Event: Card added to column (KATA)</legend>

	<div class="cerb-code-editor-toolbar">
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
		'caller_name' => 'cerb.toolbar.eventHandlers.editor',
		
		'board__context' => Context_ProjectBoard::ID,
		'board_id' => $board->id,
		
		'board_column__context' => Context_ProjectBoardColumn::ID,
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

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>

	<textarea name="functions_kata" data-editor-mode="ace/mode/cerb_kata">{$model->functions_kata}</textarea>

	{if $trigger_card_action_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_card_action_ext->getEventPlaceholders()}
	{/if}
</fieldset>

<fieldset class="peek" data-cerb-editor-toolbar>
	<legend>Toolbar: (KATA)</legend>

	<div class="cerb-code-editor-toolbar">
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
		'caller_name' => 'cerb.toolbar.editor',
			
		'board__context' => Context_ProjectBoard::ID,
		'board_id' => $board->id,
			
		'board_column__context' => Context_ProjectBoardColumn::ID,
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

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>

		<button type="button" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-circle-question-mark"></span></button>
	</div>

	<textarea name="toolbar_kata" data-editor-mode="ace/mode/cerb_kata">{$model->toolbar_kata}</textarea>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this project board column?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="create"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
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
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('.chooser-abstract').cerbChooserTrigger();

		// Cards

		var $cards = $popup.find('[data-cerb-editor-cards]');

		var $editor_cards = $cards.find('textarea[name=cards_kata]')
			.cerbCodeEditor()
			.nextAll('pre.ace_editor')
		;

		var editor_cards = ace.edit($editor_cards.attr('id'));

		var $toolbar_cards = $cards.find('.cerb-code-editor-toolbar').cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					selected_text: ''
				}
			},
			width: '75%',
			start: function(formData) {
				var pos = editor_cards.getCursorPosition();
				var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor_cards).join('');

				formData.set('caller[params][selected_text]', editor_cards.getSelectedText());
				formData.set('caller[params][token_path]', token_path);
				formData.set('caller[params][cursor_row]', pos.row);
				formData.set('caller[params][cursor_column]', pos.column);
				formData.set('caller[params][trigger]', 'cerb.trigger.projectBoard.renderCard');
				formData.set('caller[params][value]', editor_cards.getValue());
			},
			done: function(e) {
				e.stopPropagation();

				var $target = e.trigger;

				if(!$target.is('.cerb-bot-trigger'))
					return;

				if (e.eventData.exit === 'error') {

				} else if(e.eventData.exit === 'return') {
					Devblocks.interactionWorkerPostActions(e.eventData, editor_cards);
				}
			}
		});
		
		$toolbar_cards.cerbCodeEditorToolbarEventHandler({
			editor: editor_cards
		});

		// Functions

		var $functions = $popup.find('[data-cerb-editor-functions]');

		var $editor_functions = $functions.find('textarea[name=functions_kata]')
			.cerbCodeEditor()
			.cerbCodeEditorAutocompleteKata({
				autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
			})
			.nextAll('pre.ace_editor')
		;

		var editor_functions = ace.edit($editor_functions.attr('id'));

		var $toolbar_functions = $functions.find('.cerb-code-editor-toolbar').cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					selected_text: ''
				}
			},
			width: '75%',
			start: function(formData) {
				var pos = editor_functions.getCursorPosition();
				var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor_functions).join('');

				formData.set('caller[params][selected_text]', editor_functions.getSelectedText());
				formData.set('caller[params][token_path]', token_path);
				formData.set('caller[params][cursor_row]', pos.row);
				formData.set('caller[params][cursor_column]', pos.column);
				formData.set('caller[params][trigger]', 'cerb.trigger.projectBoard.cardAction');
				formData.set('caller[params][value]', editor_functions.getValue());
			},
			done: function(e) {
				e.stopPropagation();

				var $target = e.trigger;

				if(!$target.is('.cerb-bot-trigger'))
					return;

				if (e.eventData.exit === 'error') {

				} else if(e.eventData.exit === 'return') {
					Devblocks.interactionWorkerPostActions(e.eventData, editor_functions);
				}
			}
		});
		
		$toolbar_functions.cerbCodeEditorToolbarEventHandler({
			editor: editor_functions
		});

		// Toolbar

		var $toolbar = $popup.find('[data-cerb-editor-toolbar]');

		var $editor_toolbar = $toolbar.find('textarea[name=toolbar_kata]')
			.cerbCodeEditor()
			.cerbCodeEditorAutocompleteKata({
				autocomplete_suggestions: cerbAutocompleteSuggestions.kataToolbar
			})
			.nextAll('pre.ace_editor')
		;

		var editor_toolbar = ace.edit($editor_toolbar.attr('id'));

		$toolbar.find('.cerb-code-editor-toolbar').cerbToolbar({
			caller: {
				name: 'cerb.toolbar.editor',
				params: {
					toolbar: 'cerb.toolbar.projectBoardColumn',
					selected_text: ''
				}
			},
			start: function(formData) {
				formData.set('caller[params][selected_text]', editor_toolbar.getSelectedText())
			},
			done: function(e) {
				e.stopPropagation();

				var $target = e.trigger;

				if(!$target.is('.cerb-bot-trigger'))
					return;

				if (e.eventData.exit === 'error') {

				} else if(e.eventData.exit === 'return') {
					Devblocks.interactionWorkerPostActions(e.eventData, editor_toolbar);
				}
			}
		});

		$popup.find('input[name=name]').focus();

	});
});
</script>
