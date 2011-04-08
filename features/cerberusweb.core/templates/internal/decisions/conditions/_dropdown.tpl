<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
	
	{if $condition.type == 'X'}{* Multi-picklist + multi-checkbox *}
	<option value="is" {if $params.oper=='is'}selected="selected"{/if}>is all of</option>
	<option value="!is" {if $params.oper=='!is'}selected="selected"{/if}>is not all of</option>
	{/if}
</select>
<br>

{foreach from=$condition.options item=opt}
<label><input type="checkbox" name="{$namePrefix}[values][]" value="{$opt}" {if in_array($opt,$params.values)}checked="checked"{/if}> {$opt}</label><br>
{/foreach}
