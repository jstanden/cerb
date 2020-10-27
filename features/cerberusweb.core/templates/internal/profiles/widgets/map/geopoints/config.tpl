<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Country" class="peek">
		<legend>{'common.map'|devblocks_translate|capitalize}</legend>
		
		<select name="params[projection]">
			<option value="world" {if $widget->extension_params.projection != 'usa'}selected="selected"{/if}>World (Countries)</option>
			<option value="usa" {if $widget->extension_params.projection == 'usa'}selected="selected"{/if}>U.S. (States)</option>
		</select>
	</fieldset>

	<fieldset data-cerb-events-map-get-points>
		<legend>Event: Get map points (KATA)</legend>
		<div class="cerb-code-editor-toolbar">
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			])}

			{$toolbar_kata =
"interaction/automation:
  icon: circle-plus
  #label: Automation
  uri: ai.cerb.eventHandler.automation
  inputs:
    trigger: cerb.trigger.widgetMap.getPoints
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

			<div class="cerb-code-editor-toolbar-divider"></div>
		</div>
		<textarea name="params[automation_getpoints]" data-editor-mode="ace/mode/cerb_kata">{$model->extension_params.automation_getpoints}</textarea>
	</fieldset>

	<fieldset data-cerb-events-map-render-point>
		<legend>Event: Render map point (KATA)</legend>
		<div class="cerb-code-editor-toolbar">
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			])}

			{$toolbar_kata =
"interaction/automation:
  icon: circle-plus
  #label: Automation
  uri: ai.cerb.eventHandler.automation
  inputs:
    trigger: cerb.trigger.widgetMap.renderPoint
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

			<div class="cerb-code-editor-toolbar-divider"></div>
		</div>
		<textarea name="params[automation_renderpoint]" data-editor-mode="ace/mode/cerb_kata">{$model->extension_params.automation_renderpoint}</textarea>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');

	$config.find('textarea[data-editor-mode]')
		.cerbCodeEditor()
		;

	var $fieldset_get_points = $config.find('[data-cerb-events-map-get-points]');
	var $fieldset_render_point = $config.find('[data-cerb-events-map-render-point]');
	
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
			var $toolbar = $target.closest('.cerb-code-editor-toolbar');
			var $automation_editor = $toolbar.nextAll('pre.ace_editor');

			var automation_editor = ace.edit($automation_editor.attr('id'));
			automation_editor.insertSnippet(e.eventData.return.snippet);
		}
	};
	
	// Toolbars
	
	$fieldset_get_points.find('.cerb-code-editor-toolbar')
		.cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					trigger: 'cerb.trigger.widgetMap.getPoints',
					selected_text: ''
				}
			},
			start: function(formData) {
			},
			done: doneFunc
		})
	;
	
	$fieldset_render_point.find('.cerb-code-editor-toolbar')
		.cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					trigger: 'cerb.trigger.widgetMap.renderPoint',
					selected_text: ''
				}
			},
			start: function(formData) {
			},
			done: doneFunc
		})
	;
});
</script>