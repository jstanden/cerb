<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Languages:</b><br>
{foreach from=$langs item=lang key=lang_code}
<label><input name="lang_ids[]" type="checkbox" value="{$lang_code}"><span style="">{$lang}</span></label><br>
{/foreach}

