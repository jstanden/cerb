<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

{foreach from=$options item=opt key=k}
<label><input type="checkbox" name="{$namePrefix}[values][]" value="{$k}" {if in_array($opt,$params.values)}checked="checked"{/if}> {$opt}</label><br>
{/foreach}
