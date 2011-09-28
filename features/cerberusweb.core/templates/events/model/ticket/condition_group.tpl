<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

{foreach from=$groups item=group key=group_id}
	<label><input type="checkbox" name="{$namePrefix}[group_id][]" value="{$group_id}" {if in_array($group_id,$params.group_id)}checked="checked"{/if}> {$group->name}</label><br>
{/foreach}

