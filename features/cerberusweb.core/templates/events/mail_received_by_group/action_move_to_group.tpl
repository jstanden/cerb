<b>{'common.group'|devblocks_translate|capitalize}:</b>
<select name="{$namePrefix}[group_id]">
	{foreach from=$groups item=group key=group_id}
	<option value="{$group_id}" {if $group_id==$params.group_id}selected="selected"{/if}>{$group->name}</option>
	{/foreach}
</select>
<br>
