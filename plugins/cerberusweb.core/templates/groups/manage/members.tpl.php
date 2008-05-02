<div class="block">

<blockquote style="margin:5px;">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="groups">
	<input type="hidden" name="a" value="saveTabMembers">
	<input type="hidden" name="team_id" value="{$team->id}">

	<table cellspacing="0" cellpadding="3" border="0">
	<tr>
		<td colspan="2"><h2>Workers</h2></td>
	</tr>
	{foreach from=$workers item=worker key=worker_id name=workers}
		{assign var=member value=$members.$worker_id}
		<tr>
			<td style="padding-left:20px;">
				<input type="hidden" name="worker_ids[]" value="{$worker_id}">
				<select name="worker_levels[]">
					<option value="">&nbsp;</option>
					<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
					<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
				</select>
				<span style="{if $member}font-weight:bold;{/if}">{$worker->getName()}</span>
				{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}
			</td>
		</tr>
	{/foreach}
	</table>
	<br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>	
	</form>
</blockquote>

</div>

