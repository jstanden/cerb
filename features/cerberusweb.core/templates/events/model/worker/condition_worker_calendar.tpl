<select name="{$namePrefix}[is_available]">
	<option value="1" {if $params.is_available}selected="selected"{/if}>available</option>
	<option value="0" {if !$params.is_available}selected="selected"{/if}>busy</option>
</select>

between

<input type="text" name="{$namePrefix}[from]" size="15" value="{$params.from}" placeholder="now">
and
<input type="text" name="{$namePrefix}[to]" size="15" value="{$params.to}" placeholder="+5 mins">
<br>

<i>(e.g. between "now" and "+5 mins"; or between "today" and "tomorrow")</i>