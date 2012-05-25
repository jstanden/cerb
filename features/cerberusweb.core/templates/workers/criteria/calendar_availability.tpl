<b>Is:</b><br>
<select name="is_available">
	<option value="1" {if $param->value.2}selected="selected"{/if}>Available</option>
	<option value="0" {if !empty($param->value) && empty($param->value.2)}selected="selected"{/if}>Busy</option>
</select>
<br>
<br>

<b>{$translate->_('search.date.between')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" id="searchDateFrom" name="from" size="20" value="{if !is_null($param->value.0)}{$param->value.0}{/if}" style="width:98%;"><br>
	-{$translate->_('search.date.between.and')}-<br>
	<input type="text" id="searchDateTo" name="to" size="20" value="{if !is_null($param->value.1)}{$param->value.1}{else}now{/if}" style="width:98%;"><br>
	<br>
	{$translate->_('search.date.examples')|escape|nl2br nofilter}
</blockquote>
