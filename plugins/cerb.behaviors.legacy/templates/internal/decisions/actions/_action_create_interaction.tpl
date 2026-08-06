<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">On these workers</label>
	<select name="{$namePrefix}[on]" class="on">
		<option value=""></option>
		{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
		{if $context_data.context == CerberusContexts::CONTEXT_WORKER}
		<option value="{$val_key}" context="{$context_data.context}" {if $params.on==$val_key}selected="selected"{/if}>{$context_data.label}</option>
		{/if}
		{/foreach}
	</select>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Send the interaction to this behavior</label>
	{$behavior = DAO_TriggerEvent::get($params.behavior_id)}
	<div class="cerb-ui-record-chooser" id="{$namePrefix}_behavior_chooser">
		{if $behavior}
			<li data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
		{/if}
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.interaction'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">e.g. "tickets.find.me"</span></label>
	<input type="text" name="{$namePrefix}[interaction]" value="{$params.interaction}" class="placeholders" placeholder="e.g. tickets.find">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.params'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[interaction_params_json]" rows="3" style="height:{$textarea_height|default:'6em'};" class="placeholders">{$params.interaction_params_json}</textarea>
	<div>
		JSON object: <tt>{literal}{"key":"value", ...}{/literal}</tt>
		<br>
		Keys and values must be strings. Keys may only contain letters, numbers, and dash (-).
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.expires'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">e.g. "1 week"; leave blank for indefinite</span></label>
	<input type="text" name="{$namePrefix}[expires]" value="{$params.expires}" class="placeholders" placeholder="e.g. 1 week">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Create interactions in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $behavior_params = $action.find('div.parameters');
	var $bubbles = $action.find('ul.chooser-container');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	if(window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser($action.find('#{$namePrefix}_behavior_chooser')[0], { context: '{CerberusContexts::CONTEXT_BEHAVIOR}', name: '{$namePrefix}[behavior_id]', emptyIcon: 'branch', query: "event:{$event_point|default:'event.interaction.chat.worker'} disabled:n bot.id:{$trigger->bot_id}" });
});
</script>
