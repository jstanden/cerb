<b>Behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[behavior_id]">
	{foreach from=$macros item=macro key=macro_id}
		<option value="{$macro_id}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
	{/foreach}
	</select>
</div>

<div class="parameters">
{$behavior_id = $params.behavior_id}
{if empty($behavior_id) || !isset($macros.$behavior_id)}
	{$behavior_id = key($macros)}
{/if}
{include file="devblocks:cerberusweb.core::events/action_schedule_behavior_params.tpl" params=$params macro_params=$macros.{$behavior_id}->variables}
</div>

<b>When should this behavior happen?</b> (default: now)
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[run_date]" value="{if empty($params.run_date)}now{else}{$params.run_date}{/if}" size="45" style="width:100%;">
	<br>
	<i>e.g. +2 days; next Monday; tomorrow 8am; 5:30pm; Dec 21 2012</i>
</div>

<b>If duplicate behavior is scheduled:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[on_dupe]" value="" {if empty($params.on_dupe)}checked="checked"{/if}> Allow multiple occurrences</label><br>
	<label><input type="radio" name="{$namePrefix}[on_dupe]" value="first" {if 'first'==$params.on_dupe}checked="checked"{/if}> Only schedule earliest occurrence</label><br>
	<label><input type="radio" name="{$namePrefix}[on_dupe]" value="last" {if 'last'==$params.on_dupe}checked="checked"{/if}> Only schedule latest occurrence</label><br>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('select:first').change(function(e) {
	$div = $action.find('div.parameters');
	genericAjaxGet($div,'c=internal&a=showScheduleBehaviorParams&name_prefix={$namePrefix}&trigger_id=' + $(this).val());
});
</script>
