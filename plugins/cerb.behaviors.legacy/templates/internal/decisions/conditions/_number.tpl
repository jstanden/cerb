<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper=='is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper=='!is'}selected="selected"{/if}>is not</option>
	<option value="gt" {if $params.oper=='gt'}selected="selected"{/if}>is greater than</option>
	<option value="lt" {if $params.oper=='lt'}selected="selected"{/if}>is less than</option>
</select>

<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="16">
