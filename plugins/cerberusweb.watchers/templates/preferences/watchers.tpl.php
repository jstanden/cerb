<h2>Mail Forwarding</h2>
You can create a mail forward to have copies of group mail sent to any e-mail address, such as 
your desktop e-mail client or handheld device.<br>
You can reply normally to this forwarded mail as if it was sent directly to you -- the helpdesk 
will route it back to the appropriate people.<br>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="core.pref.notifications">

{if !empty($memberships)}
<table cellspacing="0" cellpadding="2" border="0">
<tr>
	<td style="padding-right:10px;"><b>For mail in group/bucket:</b></td>
	<td style="padding-right:10px;"><b>Forward a copy to:</b></td>
	<td style="padding-right:10px;"><b>For which mail events?</b></td>
	<td><b>Delete</b></td>
</tr>
{foreach from=$notifications item=forward name=forwards key=forward_id}
	<tr>
		<td style="padding-right:10px;">
			{assign var=forward_bucket_gid value=$forward->group_id}
			{assign var=forward_bucket_id value=$forward->bucket_id}
			
			{$groups.$forward_bucket_gid->name}: 
			{if $forward_bucket_id==-1}
				All
			{elseif $forward_bucket_id==0}
				Inbox
			{else}
				{assign var=group_bkts value=$group_buckets.$forward_bucket_gid}
				{$group_bkts.$forward_bucket_id->name}
			{/if}
		</td>
		<td>
			{$forward->email}
		</td>
		<td>
			{if $forward->event=='i'}
				Incoming mail
			{elseif $forward->event=='o'}
				Outgoing mail
			{elseif $forward->event=='io'}
				Incoming+Outgoing
			{elseif $forward->event=='r'}
				Replies to me
			{/if}
		</td>
		<td align="center">
			<input type="checkbox" name="forward_deletes[]" value="{$forward->id}">
		</td>
	</tr>
{/foreach}
	<tr>
		<td style="padding-right:10px;">
			<b>Add:</b> <select name="forward_bucket">
				<option value="">-- choose a bucket --</option>
				{foreach from=$memberships item=group key=group_id}
					<optgroup label="{$groups.$group_id->name}">
					<option value="{$group_id}_-1">{$groups.$group_id->name}: -- All --</option>
					<option value="{$group_id}_0">{$groups.$group_id->name}: Inbox</option>
					{foreach from=$group_buckets.$group_id item=bucket key=bucket_id}
					<option value="{$group_id}_{$bucket_id}">{$groups.$group_id->name}: {$bucket->name}</option>
					{/foreach}
					</optgroup>
				{/foreach}
			</select>
		</td>
		<td>
			<select name="forward_address">
				{foreach from=$addresses item=address}
					{if $address->is_confirmed}
					<option value="{$address->address}">{$address->address}</option>
					{/if}
				{/foreach}
			</select>
		</td>
		<td>
			<select name="forward_event">
				<option value="i">Incoming mail
				<option value="o">Outgoing mail
				<option value="io" selected>Incoming+Outgoing
				<option value="r">Replies to me
			</select>
		</td>
	</tr>
</table>

{else}
You are not a member of any groups.<br>
{/if}

<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>
