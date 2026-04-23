<select name="{$namePrefix}[oper]">
	<option value="between" {if $params.oper == 'between'}selected="selected"{/if}>is between</option>
	<option value="!between" {if $params.oper == '!between'}selected="selected"{/if}>is not between</option>
</select>

<input type="text" name="{$namePrefix}[from]" size="10" value="{$params.from}">
and
<input type="text" name="{$namePrefix}[to]" size="10" value="{$params.to}">

<br>
<i>(e.g. between "noon" and "4:15 pm"; or between "8AM" and "17:00")</i>