<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Service Levels:</b><br>
<label><input name="sla_ids[]" type="checkbox" value="0"><span style="font-weight:bold;color:rgb(0,120,0);">None</span></label><br>
{foreach from=$slas item=sla key=sla_id}
<label><input name="sla_ids[]" type="checkbox" value="{$sla_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$sla->name}</span></label><br>
{/foreach}
