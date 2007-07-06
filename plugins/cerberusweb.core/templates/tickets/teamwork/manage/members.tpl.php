{include file="$path/tickets/teamwork/manage/menu.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamMembers">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>Members</h2>

	<div style="margin-left:20px">
	<!-- Members are responsible for all buckets by default.<br> -->
	<br>

	<table cellpadding="0" cellspacing="0" border="0">	
	{foreach from=$members item=member key=member_id name=members}
		<tr>
			<td width="100%"><h3>{$member->getName()}{if !empty($member->title)} ({$member->title}){/if}</h3></td>
			<td width="0%" nowrap="nowrap">
				<label><input type="checkbox" name="member_deletes[]" value="{$member->id}"> Remove {$member->getName()}</label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<blockquote>
					<!-- 
					<input type="hidden" name="member_ids[]" value="{$member->id}">
					<label><input type="checkbox" name="work_mode[]" value="1" onclick="toggleDiv('buckets_{$member->id}',this.checked?'block':'none')">Only responsible for selected buckets:</label>
					<div id="buckets_{$member->id}" style="display:none;">
						<select name="member_buckets[]" size="5" multiple="multiple">
							<option value="0">Sales
							<option value="0">Support
							<option value="0">Development
							<option value="0">Bugs
							<option value="0">Servers
						</select>
					</div>
					 -->
				</blockquote>
			</td>
		</tr>
	{/foreach}
	</table>
	
	{if !empty($available_workers)}
	<br>
	<b>Available Workers:</b><br>
	<select name="member_adds[]" size="5" multiple="multiple">
		{foreach from=$available_workers item=worker name=workers key=worker_id}
			<option value="{$worker->id}">{$worker->getName()}{if !empty($worker->title)} ({$worker->title}){/if}</option>
		{/foreach}
	</select><br>
	(Tip: Hold the <i>Control</i> or <i>Option</i> key to select multiple members)<br>
	{/if}
	</div>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</div>

</form>