<h2>{$translate->_('watchers.ui.pref.mail_forwarding')}</h2>
{$translate->_('watchers.ui.pref.any_email')}<br>
{$translate->_('watchers.ui.pref.reply_normally')}<br>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="core.pref.notifications">

{if !empty($memberships)}
<table cellspacing="0" cellpadding="2" border="0">
<tr>
	<td style="padding-right:10px;"><b>{$translate->_('watchers.ui.pref.group_bucket')}</b></td>
	<td style="padding-right:10px;"><b>{$translate->_('watchers.ui.pref.forward_copy')}</b></td>
	<td style="padding-right:10px;"><b>{$translate->_('watchers.ui.pref.which_events')}</b></td>
	<td><b>{$translate->_('common.delete')|capitalize}</b></td>
</tr>
{foreach from=$notifications item=forward name=forwards key=forward_id}
	<tr>
		<td style="padding-right:10px;">
			{assign var=forward_bucket_gid value=$forward->group_id}
			{assign var=forward_bucket_id value=$forward->bucket_id}
			
			{$groups.$forward_bucket_gid->name}: 
			{if $forward_bucket_id==-1}
				{$translate->_('common.all')|capitalize}
			{elseif $forward_bucket_id==0}
				{$translate->_('common.inbox')|capitalize}
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
				{$translate->_('watchers.ui.pref.incoming')}
			{elseif $forward->event=='o'}
				{$translate->_('watchers.ui.pref.outgoing')}
			{elseif $forward->event=='io'}
				{$translate->_('watchers.ui.pref.incoming_outgoing')}
			{elseif $forward->event=='r'}
				{$translate->_('watchers.ui.pref.replies_to_me')}
			{/if}
		</td>
		<td align="center">
			<input type="checkbox" name="forward_deletes[]" value="{$forward->id}">
		</td>
	</tr>
{/foreach}
	<tr>
		<td style="padding-right:10px;">
			<b>{$translate->_('watchers.ui.pref.add')}</b> <select name="forward_bucket">
				<option value="">-- {$translate->_('watchers.ui.pref.choose_bucket')} --</option>
				{foreach from=$memberships item=group key=group_id}
					<optgroup label="{$groups.$group_id->name}">
					<option value="{$group_id}_-1">{$groups.$group_id->name}: -- {$translate->_('common.all')|capitalize} --</option>
					<option value="{$group_id}_0">{$groups.$group_id->name}: {$translate->_('common.inbox')|capitalize}</option>
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
				<option value="i">{$translate->_('watchers.ui.pref.incoming')}
				<option value="o">{$translate->_('watchers.ui.pref.outgoing')}
				<option value="io" selected>{$translate->_('watchers.ui.pref.incoming_outgoing')}
				<option value="r">{$translate->_('watchers.ui.pref.replies_to_me')}
			</select>
		</td>
	</tr>
</table>
<br>

{if !empty($addresses)}
{$translate->_('watchers.ui.pref.assignment_notify')}
<select name="assign_notify_email">
	<option value="">-- {$translate->_('watchers.ui.pref.dont_notify')} --</option>
	{foreach from=$addresses item=address}
		{if $address->is_confirmed}
		<option value="{$address->address}" {if $address->address==$assign_notify_email}selected{/if}>{$address->address}</option>
		{/if}
	{/foreach}
</select>
<br>
<br>
{/if}

{else}
{$translate->_('watchers.ui.pref.not_group_member')}<br>
{/if}

<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>
