<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Groups:</b><br>
{foreach from=$teams item=team key=team_id}
<label><input name="team_id[]" type="checkbox" value="{$team_id}" onchange="toggleDiv('searchGroup{$team_id}',(this.checked)?'block':'none');"><span style="font-weight:bold;color:rgb(0,120,0);">{$team->name}</span></label><br>
<blockquote style="margin:0px;margin-left:10px;display:none;" id="searchGroup{$team_id}">
	<label><input name="bucket_id[]" type="checkbox" value="0"><span style="font-size:90%;">Inbox</span></label><br>
	{if isset($team_categories.$team_id)}
	{foreach from=$team_categories.$team_id item=cat}
		<label><input name="bucket_id[]" type="checkbox" value="{$cat->id}"><span style="font-size:90%;">{$cat->name}</span></label><br>
	{/foreach}
	{/if}
</blockquote>
{/foreach}

<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>