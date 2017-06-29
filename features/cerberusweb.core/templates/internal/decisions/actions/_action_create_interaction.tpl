<b>On these workers:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	<option value=""></option>
	{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
	{if $context_data.context == CerberusContexts::CONTEXT_WORKER}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on==$val_key}selected="selected"{/if}>{$context_data.label}</option>
	{/if}
	{/foreach}
</select>
</div>

<b>Send the interaction to this behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{$behavior = DAO_TriggerEvent::get($params.behavior_id)}
	<button type="button" class="chooser-abstract" data-field-name="{$namePrefix}[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="bot.id:{$trigger->bot_id}" data-query-required="event:{$event_point|default:'event.interaction.chat.worker'} disabled:n"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $behavior}
			<li><input type="hidden" name="{$namePrefix}[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
		{/if}
	</ul>
</div>

<b>{'common.interaction'|devblocks_translate|capitalize}:</b> (e.g. "tickets.find.me")
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

<b>{'common.expires'|devblocks_translate|capitalize}:</b> (e.g. "1 week"; leave blank for indefinite)
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[expires]" style="width:100%;" value="{$params.expires}" class="placeholders" placeholder="e.g. 1 week">
</div>

<b>Create interactions in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	var $behavior_params = $action.find('div.parameters');
	var $bubbles = $action.find('ul.chooser-container');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$action.find('.chooser-abstract')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
			})
	;
});
</script>
