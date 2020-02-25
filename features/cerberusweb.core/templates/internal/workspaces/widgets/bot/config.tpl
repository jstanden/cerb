<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Behavior" class="peek">
		<legend>Render the widget using this bot behavior:</legend>
		
		{$behavior_id = $widget->params.behavior_id}
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
		
		<div class="parameters">
		{if $behavior}
		{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" namePrefix="params[behavior_vars]" params=$widget->params.behavior_vars macro_params=$behavior->variables}
		{/if}
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset = $('fieldset#widget{$widget->id}Behavior');
	var $bubbles = $fieldset.find('ul.chooser-container');
	var $behavior_params = $fieldset.find('div.parameters');
	
	$fieldset.find('.chooser-behavior')
	.cerbChooserTrigger()
		.on('cerb-chooser-saved', function(e) {
			var $bubble = $bubbles.find('> li:first input:hidden');
			var id = $bubble.first().val();
			
			if(id) {
				genericAjaxGet($behavior_params,'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix=params[behavior_vars]&trigger_id=' + encodeURIComponent(id));
			} else {
				$behavior_params.html('');
			}
		})
	;
});
</script>