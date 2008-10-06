<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Source:</b><br>
{foreach from=$sources item=source key=source_id}
<label><input name="source_ids[]" type="checkbox" value="{$source_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$source->getSourceName()}</span></label><br>
{/foreach}
