<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Behavior" class="peek">
		<legend>Render the widget using this bot behavior:</legend>

		{$behavior_id = $widget->params.behavior_id}
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

		<div class="parameters">
		{if $behavior}
		{include file="devblocks:cerb.behaviors.legacy::events/_action_behavior_params.tpl" namePrefix="params[behavior_vars]" params=$widget->params.behavior_vars macro_params=$behavior->variables}
		{/if}
		</div>
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $fieldset = $('fieldset#widget{$widget->id}Behavior');
	const $behavior_params = $fieldset.find('div.parameters');

	if(window.CerbUI && CerbUI.RecordChooser)
		$fieldset.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
				name: 'params[behavior_id]',
				emptyIcon: 'branch',
				query: 'event:event.dashboard.widget.render disabled:n',
				onSelect: function(item) {
					if(item.id)
						genericAjaxGet($behavior_params, 'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix=params[behavior_vars]&trigger_id=' + encodeURIComponent(item.id));
					else
						$behavior_params.html('');
				}
			});
		});
});
</script>
