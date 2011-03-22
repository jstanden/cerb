<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper=='is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper=='!is'}selected="selected"{/if}>is not</option>
</select>
<br>
<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="45" style="width:100%;"><br>
