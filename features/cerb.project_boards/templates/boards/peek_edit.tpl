{$peek_context = Context_ProjectBoard::ID}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
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
		<li><a href="#board-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#board-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="board-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="board-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
					</td>
				</tr>

				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				{/if}
			</tbody>
		</table>


		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

		<fieldset class="peek" data-cerb-editor-cards>
			<legend>Event: Render card (KATA)</legend>

			<div class="cerb-code-editor-toolbar">
				{$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.eventHandlers.editor',
				
				'board__context' => $peek_context,
				'board_id' => $peek_context_id,
				
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
				])}

				{$toolbar_kata =
"menu/add:
  icon: circle-plus
  items:
    interaction/automation:
      label: Automation
      uri: ai.cerb.eventHandler.automation
      inputs:
        trigger: cerb.trigger.projectBoard.renderCard
"}

				{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

				<div class="cerb-code-editor-toolbar-divider"></div>
				{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
			</div>
			
			<textarea name="cards_kata" data-editor-mode="ace/mode/cerb_kata">{$model->cards_kata}</textarea>
			
			{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_ProjectBoardRenderCard::ID, true)}
			{if $trigger_ext}
				{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
			{/if}
		</fieldset>

		{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this project board?
			</div>
			
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="buttons" style="margin-top:10px;">
			{if $model->id}
				<button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
			{else}
				<button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'projects.common.board'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		var $fieldset_cards = $popup.find('fieldset[data-cerb-editor-cards]');

		// Buttons
		
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});

		// Editors

		var $cards_editor = $fieldset_cards.find('textarea[name=cards_kata]')
			.cerbCodeEditor()
			.nextAll('pre.ace_editor')
		;

		var cards_editor = ace.edit($cards_editor.attr('id'));

		// Toolbar

		var $cards_toolbar = $fieldset_cards.find('.cerb-code-editor-toolbar').cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					trigger: 'cerb.trigger.projectBoard.renderCard',
					selected_text: ''
				}
			},
			start: function(formData) {
				formData.set('caller[params][selected_text]', cards_editor.getSelectedText())
			},
			done: function(e) {
				e.stopPropagation();

				var $target = e.trigger;

				if (!$target.is('.cerb-bot-trigger'))
					return;

				if(!e.eventData || !e.eventData.exit)
					return;

				if (e.eventData.exit === 'error') {
					// [TODO] Show error

				} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
					cards_editor.insertSnippet(e.eventData.return.snippet);
				}
			}
		});
		
		$cards_toolbar.cerbCodeEditorToolbarEventHandler({
			editor: cards_editor
		});

		// Package Library
		
		{if !$model->id && $packages}
			var $library_container = $popup.find('.cerb-tabs').tabs();
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function(e) {
				$popup.one('peek_saved peek_error', function(e) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});
				
				$popup.find('button.save').click();
			});
		{/if}
		
	});
});
</script>
