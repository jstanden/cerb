<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Behavior" class="peek">
		<legend>Render the widget using this bot behavior:</legend>
		
		{$behavior_id = $widget->extension_params.behavior_id}
		{$behavior = null}
		{if $behavior_id}
			{$behavior = DAO_TriggerEvent::get($behavior_id)}
		{/if}
		<div style="margin-left:10px;margin-bottom:0.5em;">
			<div class="cerb-ui-record-chooser">
				{if $behavior}
					<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
				{/if}
			</div>
		</div>
		
		<div class="parameters" {if !$behavior}style="display:none;"{/if}>
			<b>Parameters (JSON):</b>
			<textarea id="widget{$widget->id}BehaviorParamsEditor" name="params[behavior_params_json]" data-editor-lines="8" spellcheck="false">{$widget->extension_params.behavior_params_json}</textarea>
		</div>
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $fieldset = $('fieldset#widget{$widget->id}Behavior');
	const $behavior_params = $fieldset.find('div.parameters');

	const ed = new CerbUI.ScriptingEditor(document.getElementById('widget{$widget->id}BehaviorParamsEditor'), { minLines: 3 });

	if(window.CerbUI && CerbUI.RecordChooser)
		$fieldset.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
				name: 'params[behavior_id]',
				emptyIcon: 'branch',
				query: 'event:event.dashboard.widget.render disabled:n',
				onSelect: function(item) {
					if(item.id) {
						genericAjaxGet('', 'c=profiles&a=invoke&module=behavior&action=getParamsAsJson&trigger_id=' + encodeURIComponent(item.id), function(txt) {
							ed.setValue(txt);
							$behavior_params.fadeIn();
						});
					} else {
						ed.setValue('');
						$behavior_params.hide();
					}
				}
			});
		});
});
</script>