{if is_array($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	<option value=""></option>
	{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
	{$event_point = $context_to_macros.{$context_data.context}}
	{if $event_point && !$context_data.is_polymorphic}
	<option value="{$val_key}" data-event="{$event_point}" {if $params.on==$val_key}{$selected_event = $event_point}selected="selected"{/if}>{$context_data.label}</option>
	{elseif $context_data.is_polymorphic}
	<option value="{$val_key}" data-event="event.macro.*" {if $params.on==$val_key}{$selected_event = $event_point}selected="selected"{/if}>{$context_data.label}</option>
	{/if}
	{/foreach}
</select>
</div>
{/if}

<b>Run this behavior:</b>
{$behavior = null}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<div class="cerb-ui-record-chooser">
		{if $params.behavior_id}
			{$behavior = DAO_TriggerEvent::get($params.behavior_id)}
			{if $behavior}
				<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
			{/if}
		{/if}
	</div>
</div>

<div class="parameters">
{if $behavior}
{include file="devblocks:cerb.behaviors.legacy::events/_action_behavior_params.tpl" params=$params macro_params=$behavior->variables}
{/if}
</div>

<b>Also run behavior in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save behavior data to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="24" value="{if !empty($params.var)}{$params.var}{else}_behavior{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $action = $('#{$namePrefix}_{$nonce}');
	const $behavior_params = $action.find('div.parameters');

	if(!(window.CerbUI && CerbUI.RecordChooser))
		return;

	function loadParams(id) {
		if(id)
			genericAjaxGet($behavior_params, 'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix={$namePrefix}&trigger_id=' + id);
		else
			$behavior_params.html('');
	}

	const rc = new CerbUI.RecordChooser($action.find('.cerb-ui-record-chooser')[0], {
		context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
		name: '{$namePrefix}[behavior_id]',
		emptyIcon: 'branch',
		query: 'event:{$selected_event} disabled:n usableBy.bot:{$trigger->bot_id}',
		onSelect: function(item) { loadParams(item.id); }
	});

	// The "On:" event scopes which behaviors are pickable; changing it re-scopes the chooser and clears.
	$action.find('select.on').on('change', function() {
		$behavior_params.html('');
		const event_point = $(this).find('option:selected').attr('data-event');
		rc.setQuery('event:' + event_point + ' disabled:n usableBy.bot:{$trigger->bot_id}');
		rc.clear();
	});
});
</script>