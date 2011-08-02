<b>Behavior:</b><br>
<select name="{$namePrefix}[behavior_id]">
{foreach from=$macros item=macro key=macro_id}
	<option value="{$macro_id}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
{/foreach}
</select>
<br>
<br>

<b>When should this behavior happen?</b> (default: now)<br>
<input type="text" name="{$namePrefix}[run_date]" value="{if empty($params.run_date)}now{else}{$params.run_date}{/if}" size="45" style="width:100%;"><br>
<i>e.g. +2 days; next Monday; tomorrow 8am; 5:30pm; Dec 21 2012</i><br>
<br>

{*
<b>Relative to:</b><br>
<select name="{$namePrefix}[date_relative_to]">
	<option value="now">Current time</option>
	{foreach from=$dates item=label key=key}
		<option value="{$key}">{$label}</option>
	{/foreach}
</select>
<br>
*}