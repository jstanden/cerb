<b>Behavior:</b><br>
<select name="{$namePrefix}[behavior_id]">
{foreach from=$macros item=macro key=macro_id}
	<option value="{$macro_id}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
{/foreach}
</select>

