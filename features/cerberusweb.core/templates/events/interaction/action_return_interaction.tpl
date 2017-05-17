{$behavior = DAO_TriggerEvent::get($params.behavior_id)}

<b>Send the interaction to this behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<button type="button" class="chooser-behavior" data-field-name="{$namePrefix}[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="event:event.interaction.chat.worker disabled:n usableBy.bot:{$trigger->bot_id}"><span class="glyphicons glyphicons-search"></span></button>
	
	<ul class="bubbles chooser-container">
		{if $behavior}
			<li><input type="hidden" name="{$namePrefix}[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
		{/if}
	</ul>
</div>

<b>{'common.name'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[name]" style="width:100%;" value="{$params.name}" class="placeholders" placeholder="e.g. Find tickets from sender">
</div>

<b>{'common.interaction'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[interaction]" style="width:100%;" value="{$params.interaction}" class="placeholders" placeholder="e.g. tickets.find">
</div>

<b>{'common.params'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[interaction_params_json]" rows="3" cols="45" style="width:100%;height:{$textarea_height|default:'6em'};" class="placeholders">{$params.interaction_params_json}</textarea>
	<div>
		JSON object: <tt>{literal}{"key":"value", ...}{/literal}</tt>
		<br>
		Keys and values must be strings. Keys may only contain letters, numbers, and dash (-).
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	var $behavior_params = $action.find('div.parameters');
	var $bubbles = $action.find('ul.chooser-container');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$action.find('.chooser-behavior')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
			})
	;
});
</script>
