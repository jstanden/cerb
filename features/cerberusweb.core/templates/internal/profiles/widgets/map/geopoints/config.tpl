<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset data-cerb-event-map-get-data>
		<legend>Map configuration (KATA)</legend>
		<div class="cerb-code-editor-toolbar">
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.editor.map'
			])}

			{$toolbar_kata =
"interaction/automation:
  icon: circle-plus
  tooltip: Map
  uri: ai.cerb.editor.mapBuilder
  inputs:
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

			<div class="cerb-code-editor-toolbar-divider"></div>
		</div>
		<textarea name="params[map_kata]" data-editor-mode="ace/mode/cerb_kata">{$model->extension_params.map_kata}</textarea>
	</fieldset>

	<fieldset data-cerb-event-map-clicked>
		<legend>Event: Map clicked (KATA)</legend>
		<div class="cerb-code-editor-toolbar">
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.eventHandlers.editor'
			])}

			{$toolbar_kata =
"interaction/automation:
  icon: circle-plus
  #label: Automation
  uri: ai.cerb.eventHandler.automation
  inputs:
    trigger: cerb.trigger.map.clicked
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

			<div class="cerb-code-editor-toolbar-divider"></div>
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
		</div>
		
		<textarea name="params[automation][map_clicked]" data-editor-mode="ace/mode/cerb_kata">{$model->extension_params.automation.map_clicked}</textarea>

		{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_MapClicked::ID, true)}
		{if $trigger_ext}
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
		{/if}
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $fieldset_map_get_data = $config.find('[data-cerb-event-map-get-data]');
	var $fieldset_map_clicked = $config.find('[data-cerb-event-map-clicked]');

	$config.find('textarea[name="params[map_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaMap
		})
	;

	var $automation_editor =  $config.find('textarea[name="params[automation][map_clicked]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
		})
		.nextAll('pre.ace_editor')
	;
	
	var automation_editor = ace.edit($automation_editor.attr('id'));

	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		if(!e.eventData || !e.eventData.exit)
			return;

		if (e.eventData.exit === 'error') {
			// [TODO] Show error

		} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
			automation_editor.insertSnippet(e.eventData.return.snippet);
		}
	};
	
	// Toolbars

	$fieldset_map_get_data.find('.cerb-code-editor-toolbar')
		.cerbToolbar({
			caller: {
				name: 'cerb.toolbar.editor.map',
				params: {
					selected_text: ''
				}
			},
			start: function(formData) {
			},
			done: doneFunc
		})
	;

	var $toolbar_map_clicked = $fieldset_map_clicked.find('.cerb-code-editor-toolbar')
		.cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					trigger: 'cerb.trigger.map.clicked',
					selected_text: ''
				}
			},
			start: function(formData) {
			},
			done: doneFunc
		})
	;
	
	$toolbar_map_clicked.cerbCodeEditorToolbarEventHandler({
		editor: automation_editor
	});
});
</script>