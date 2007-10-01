<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Workers:</b><br>
<label><input name="worker_id[]" type="checkbox" value="0"><span style="font-weight:bold;color:rgb(0,120,0);">Nobody</span></label><br>
{foreach from=$workers item=worker key=worker_id}
<label><input name="worker_id[]" type="checkbox" value="{$worker_id}"><span style="color:rgb(0,120,0);">{$worker->getName()}</span></label><br>
{/foreach}

