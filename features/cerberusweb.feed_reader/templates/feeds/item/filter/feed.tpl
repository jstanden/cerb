<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<blockquote style="margin:5px;">
	{foreach from=$feeds item=feed}
		<label><input type="checkbox" name="feed_id[]" value="{$feed->id}">{$feed->name}</label><br>
	{/foreach}
</blockquote>
