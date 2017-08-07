<select name="{$namePrefix}[oper]">
	<option value="contains" {if $params.oper=='contains'}selected="selected"{/if}>contains</option>
	<option value="!contains" {if $params.oper=='!contains'}selected="selected"{/if}>does not contain</option>
</select>
<br>

<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="45" class="placeholders">
