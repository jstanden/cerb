<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Behavior" class="peek">
		<legend>Render the widget using this bot behavior:</legend>
		
		{$behavior_id = $widget->extension_params.behavior_id}
		{$behavior = null}
		{if $behavior_id}
			{$behavior = DAO_TriggerEvent::get($behavior_id)}
		{/if}
		<div style="margin-left:10px;margin-bottom:0.5em;">
			<button type="button" class="chooser-behavior" data-field-name="params[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="event:event.dashboard.widget.render disabled:n"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $behavior}
					<li><input type="hidden" name="params[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
				{/if}
			</ul>
		</div>
		
		<div class="parameters" {if !$behavior}style="display:none;"{/if}>
			<b>Parameters (JSON):</b>
			<textarea name="params[behavior_params_json]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.behavior_params_json}</textarea>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset = $('fieldset#widget{$widget->id}Behavior');
	var $frm = $fieldset.closest('form');
	
	var $bubbles_behavior = $fieldset.find('ul.chooser-container');
	
	var $behavior_params = $fieldset.find('div.parameters');
	var $textarea = $behavior_params.find('textarea');
	
	var $editor = $textarea
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
	
	$fieldset.find('.chooser-behavior')
		.cerbChooserTrigger()
		.on('cerb-chooser-saved', function(e) {
			var $bubble = $bubbles_behavior.find('> li:first input:hidden');
			var id = $bubble.first().val();
			
			if(id) {
				genericAjaxGet('','c=profiles&a=invoke&module=behavior&action=getParamsAsJson&trigger_id=' + encodeURIComponent(id), function(txt) {
					var evt = new jQuery.Event('cerb.insertAtCursor');
					evt.replace = true;
					evt.content = txt;
					$editor.trigger(evt);

					$behavior_params.fadeIn();
				});
				
			} else {
				var evt = new jQuery.Event('cerb.insertAtCursor');
				evt.replace = true;
				evt.content = '';
				$editor.trigger(evt);
				
				$behavior_params.hide();
			}
		})
		;
});
</script>