<b>Remove attachments where filename:</b>
<br>

<select name="{$namePrefix}[match_oper]">
	<option value="is" {if $params.match_oper=='is'}selected="selected"{/if}>is</option>
	{*<option value="!is" {if $params.match_oper=='!is'}selected="selected"{/if}>is not</option>*}
	<option value="like" {if $params.match_oper=='like'}selected="selected"{/if}>matches (*) wildcards</option>
	{*<option value="!like" {if $params.match_oper=='!like'}selected="selected"{/if}>does not match wildcards</option>*}
	<option value="regexp" {if $params.match_oper=='regexp'}selected="selected"{/if}>matches regular expression</option>
	{*<option value="!regexp" {if $params.match_oper=='!regexp'}selected="selected"{/if}>does not match regular expression</option>*}
</select>
<br>

<input type="text" name="{$namePrefix}[match_value]" value="{$params.match_value}" size="45">
