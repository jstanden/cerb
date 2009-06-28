<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('common.languages')|capitalize}:</b><br>
{foreach from=$langs item=lang key=lang_code}
<label><input name="lang_ids[]" type="checkbox" value="{$lang_code}"><span style="">{$lang}</span></label><br>
{/foreach}

