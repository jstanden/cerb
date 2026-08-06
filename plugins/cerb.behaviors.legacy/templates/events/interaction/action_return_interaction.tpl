{$behavior = DAO_TriggerEvent::get($params.behavior_id)}

<b>Send the interaction to this behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<div class="cerb-ui-record-chooser">
		{if $behavior}
			<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
		{/if}
	</div>
</div>

<b>{'common.name'|devblocks_translate|capitalize}:</b> (e.g. "Show my tickets")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[name]" style="width:100%;" value="{$params.name}" class="placeholders" placeholder="e.g. Find tickets from sender">
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $action = $('#{$namePrefix}_{$nonce}');

	if(window.CerbUI && CerbUI.RecordChooser)
		$action.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
				name: '{$namePrefix}[behavior_id]',
				emptyIcon: 'branch',
				query: 'bot.id:{$trigger->bot_id} event:{$event_point|default:'event.interaction.chat.worker'} disabled:n'
			});
		});
});
</script>
