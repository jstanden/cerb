{$div_id = uniqid()}
<div id="{$div_id}">
	{$behavior_id = $widget->params.behavior_id}
	{$behavior = null}
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<button type="button" class="chooser-behavior" data-field-name="params[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="event:event.dashboard.widget.get_metric disabled:n"><span class="glyphicons glyphicons-search"></span></button>
		
		<ul class="bubbles chooser-container">
			{if $behavior_id}
				{$behavior = DAO_TriggerEvent::get($behavior_id)}
				{if $behavior}
					<li><input type="hidden" name="params[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
				{/if}
			{/if}
		</ul>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $bubbles = $div.find('ul.chooser-container');
	
	$div.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$div.find('.chooser-behavior')
		.cerbChooserTrigger()
		;
});
</script>