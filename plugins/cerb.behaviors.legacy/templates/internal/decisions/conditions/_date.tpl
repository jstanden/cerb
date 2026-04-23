<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper=='is'}selected="selected"{/if}>is between</option>
	<option value="!is" {if $params.oper=='!is'}selected="selected"{/if}>is not between</option>
</select>

<input type="text" name="{$namePrefix}[from]" size="15" value="{$params.from}">
and
<input type="text" name="{$namePrefix}[to]" size="15" value="{$params.to}">
<br>

<i>(e.g. between "4/27/2001 noon" and "today 4:15 pm"; or between "Jan 1 2011 8AM" and "yesterday 17:00")</i>