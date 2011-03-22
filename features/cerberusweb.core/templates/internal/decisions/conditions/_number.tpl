<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper=='is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper=='!is'}selected="selected"{/if}>is not</option>
	<option value="gt" {if $params.oper=='gt'}selected="selected"{/if}>is greater than</option>
	<option value="lt" {if $params.oper=='lt'}selected="selected"{/if}>is less than</option>
	{*
	<option value="words_all" {if $params.oper=='words_all'}selected="selected"{/if}>contains ALL of these words</option>
	<option value="!words_all" {if $params.oper=='!words_all'}selected="selected"{/if}>does not contain ALL of these words</option>
	<option value="words_any" {if $params.oper=='words_any'}selected="selected"{/if}>contains ANY of these words</option>
	<option value="!words_any" {if $params.oper=='!words_any'}selected="selected"{/if}>does not contain ANY of these words</option>
	*}
</select>

<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="16">
