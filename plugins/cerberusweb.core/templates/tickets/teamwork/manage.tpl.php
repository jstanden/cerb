<h1>Team: {$team->name}</h1>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamManage">
<input type="hidden" name="team_id" value="{$team->id}">

<br>

<div class="block">
<h2>Members</h2>
<table cellspacing="2" cellpadding="0">
	{foreach from=$members item=member key=member_id name=members}
		<tr>
			<td>
				<input type="hidden" name="member_ids[]" value="{$member->id}">
				{$member->getName()}{if !empty($member->title)} ({$member->title}){/if}
			</td>
			<td>
				<label><input type="checkbox" name="member_deletes[]" value="{$member->id}"> Remove</label>
			</td>
		</tr>
	{/foreach}
</table>
<input type="text" name="" size="45">
<input type="button" name="" value="..." onclick=""><br>
</div>
<br>

{if !empty($categories)}
<div class="block">
<h2>Categories</h2>
<table cellspacing="2" cellpadding="0">
	<tr>
		<td>Name</td>
		<td>Tags</td>
		<td>Remove</td>
	</tr>
	{foreach from=$categories item=cat key=cat_id name=cats}
		<tr>
			<td>
				<input type="hidden" name="ids[]" value="{$cat->id}">
				<input type="text" name="names[]" value="{$cat->name}" size="35">
			</td>
			<td>
				<input type="text" name="" size="25">
				<input type="button" name="" value="..." onclick=""><br>
			</td>
			<td align="center">
				<input type="checkbox" name="deletes[]" value="{$cat_id}">
			</td>
		</tr>
	{/foreach}
</table>
</div>
<br>
{/if}

<div class="block">
<h2>Add Categories</h2>
(one label per line)<br>
<textarea rows="5" cols="45" name="add"></textarea><br>
</div>
<br>
	
<input type="submit" value="{$translate->_('common.save_changes')|capitalize}">
<a href="{devblocks_url}c=tickets&a=dashboards&team=team&id={$team->id}{/devblocks_url}">Cancel</a>
</form>