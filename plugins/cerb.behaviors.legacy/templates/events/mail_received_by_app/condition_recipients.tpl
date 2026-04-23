<select name="{$namePrefix}[oper]">
	<option value="contains" {if $params.oper=='contains'}selected="selected"{/if}>includes this address</option>
	<option value="!contains" {if $params.oper=='!contains'}selected="selected"{/if}>does not include this address</option>
</select>
<br>

<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="45">
<br>
