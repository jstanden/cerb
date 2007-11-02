{include file="$path/groups/manage/menu.tpl.php"}

<div class="block">

<blockquote style="margin:5px;">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="groups">
	<input type="hidden" name="a" value="removeTeamMember">
	<input type="hidden" name="team_id" value="{$team->id}">
	<input type="hidden" name="worker_id" value="">
	<table cellspacing="0" cellpadding="3" border="0">
	
	<tr>
		<td colspan="2"><h2>Managers</h2></td>
	</tr>
	
	{foreach from=$members item=member key=member_id name=members}
		{if $member->is_manager}
			{assign var=worker value=$workers.$member_id}
			<tr>
				<td style="padding-left:20px;">{$worker->getName()}{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}</td>
				<td align="right"><button type="button" onclick="this.form.worker_id.value='{$member_id}';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="middle"></button></td>
			</tr>
		{/if}
	{/foreach}
	
	<tr>
		<td colspan="2"><br><h2>Members</h2></td>
	</tr>
	
	{foreach from=$members item=member key=member_id name=members}
		{if !$member->is_manager}
			{assign var=worker value=$workers.$member_id}
			<tr>
				<td style="padding-left:20px;">{$worker->getName()}{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}</td>
				<td align="right"><button type="button" onclick="this.form.worker_id.value='{$member_id}';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="middle"></button></td>
			</tr>
		{/if}	
	{/foreach}
	
	</table>
	</form>
	
	{if !empty($available_workers)}
	<br>
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="groups">
	<input type="hidden" name="a" value="addTeamMember">
	<input type="hidden" name="team_id" value="{$team->id}">
	<h2>Add:</h2>
	<select name="worker_ids[]" size="5" multiple="multiple">
		{foreach from=$available_workers item=member name=members key=member_id}
			{assign var=worker value=$workers.$member_id}
			<option value="{$worker->id}">{$worker->getName()}{if !empty($worker->title)} ({$worker->title}){/if}</option>
		{/foreach}
	</select>
	<label><input type="radio" name="is_manager" value="0" checked> Member</label>
	<label><input type="radio" name="is_manager" value="1"> Manager</label>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Add</button><br>
	</form>
	{/if}
	<br>
		
</blockquote>

<!-- <br><button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>  -->
</div>

</form>