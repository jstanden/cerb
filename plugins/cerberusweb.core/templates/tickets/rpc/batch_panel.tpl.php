<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Batch Update</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="action_id" value="{$id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doBatchUpdate">
<div style="height:300px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;">

<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td colspan="2">
			<b>Apply these changes to selected tickets:</b>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set status:</td>
		<td width="100%"><select name="status">
			<option value=""></option>
			{foreach from=$statuses item=k key=v}
			<option value="{$v}">{$k}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set priority:</td>
		<td width="100%"><select name="priority">
			<option value=""></option>
			{foreach from=$priorities item=v key=k}
			<option value="{$k}">{$v}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set owner:</td>
		<td width="100%"><select name="team">
			<option value=""></option>
      		<optgroup label="Team (No Category)">
      		{foreach from=$teams item=team}
      			<option value="t{$team->id}">{$team->name}</option>
      		{/foreach}
      		</optgroup>
      		{foreach from=$team_categories item=categories key=teamId}
      			{assign var=team value=$teams.$teamId}
      			<optgroup label="{$team->name}">
      			{foreach from=$categories item=category}
    				<option value="c{$category->id}">{$category->name}</option>
    			{/foreach}
    			</optgroup>
     		{/foreach}
      	</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set training:</td>
		<td width="100%"><select name="spam">
			<option value=""></option>
			{foreach from=$training item=k key=v}
			<option value="{$v}">{$k}</option>
			{/foreach}
		</select></td>
	</tr>
</table>

</div>

<input type="button" value="{$translate->_('common.save_changes')}" onclick="ajax.saveBatchPanel('{$view_id}');">
<br>
</form>