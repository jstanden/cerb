<input type="text" name="{$namePrefix}[header]" value="{$params.header}" size="24">:
<br>

<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper=='is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper=='!is'}selected="selected"{/if}>is not</option>
	<option value="contains" {if $params.oper=='contains'}selected="selected"{/if}>contains this phrase</option>
	<option value="!contains" {if $params.oper=='!contains'}selected="selected"{/if}>does not contain this phrase</option>
	<option value="like" {if $params.oper=='like'}selected="selected"{/if}>matches (*) wildcards</option>
	<option value="!like" {if $params.oper=='!like'}selected="selected"{/if}>does not match wildcards</option>
	<option value="regexp" {if $params.oper=='regexp'}selected="selected"{/if}>matches regular expression</option>
	<option value="!regexp" {if $params.oper=='!regexp'}selected="selected"{/if}>does not match regular expression</option>
</select>
<br>

<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="24">
<br>
