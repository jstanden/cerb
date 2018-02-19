<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Behavior" class="peek">
		<legend>Render the widget using this bot behavior:</legend>
		
		{$behavior_id = $widget->params.behavior_id}
		{$behavior = null}
		<div style="margin-left:10px;margin-bottom:0.5em;">
			<button type="button" class="chooser-abstract" data-field-name="params[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="event:event.dashboard.widget.render disabled:n"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $behavior_id}
					{$behavior = DAO_TriggerEvent::get($behavior_id)}
					{if $behavior}
						<li><input type="hidden" name="params[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
					{/if}
				{/if}
			</ul>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset = $('fieldset#widget{$widget->id}Behavior');
	var $bubbles = $fieldset.find('ul.chooser-container');
});
</script>