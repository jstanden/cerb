<select name="{$namePrefix}[is_available]">
	<option value="1" {if $params.is_available}selected="selected"{/if}>available</option>
	<option value="0" {if !$params.is_available}selected="selected"{/if}>busy</option>
</select>

between

<div>
	<input type="text" name="{$namePrefix}[from]" size="45" value="{$params.from}" class="placeholders" placeholder="now" style="width:95%;">
</div>

and

<div>
	<input type="text" name="{$namePrefix}[to]" size="45" value="{$params.to}" class="placeholders" placeholder="+5 mins" style="width:95%;">
</div>

<i>(e.g. between "now" and "+5 mins"; or between "today" and "tomorrow")</i>