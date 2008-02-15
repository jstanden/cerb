<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Campaigns:</b><br>
{foreach from=$campaigns item=campaign key=campaign_id}
<label><input name="campaign_id[]" type="checkbox" value="{$campaign_id}"><span style="color:rgb(0,120,0);">{$campaign->name}</span></label><br>
{/foreach}

