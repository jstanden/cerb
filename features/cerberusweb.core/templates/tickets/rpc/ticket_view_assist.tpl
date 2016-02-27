<form action="{devblocks_url}{/devblocks_url}" method="post" id="viewAssist{$view_id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewAutoAssist">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

Sort biggest piles by: 
<label><input type="radio" name="mode" value="subjects" {if $mode!="subjects"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=subjects');"{/if} {if $mode=="subjects"}checked{/if}>Subject Similarity</label>
<label><input type="radio" name="mode" value="senders" {if $mode!="senders"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=senders');"{/if} {if $mode=="senders"}checked{/if}>Senders</label>
<br>
<br>

{if !empty($biggest)}
<div id="{$view_id}_piles" style="display:{if empty($tips)}block{else}none{/if};">
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td align="top" colspan="2">
		<H3 style="font-size:18px;margin:0px;">The biggest piles of common {if $mode=="senders"}senders{elseif $mode=="subjects"}subjects{elseif $mode=="import"}import sources{elseif $mode=="headers"}message headers{/if} in this list are:</H3>
	</td>
</tr>
<tr>
	<td width="0%" nowrap>Move to:</td>
	<td width="100%">From biggest piles:</td>
</tr>
{foreach from=$biggest item=stats key=hash}
<tr>
	<td width="1%" nowrap="nowrap" valign="middle" style="padding-right:5px;">
		<select name="piles_moveto[]" id="select{$hash}">
			<option value=""></option>
			<optgroup label="Move to..." style="color:rgb(0,150,0);font-weight:bold;">
				{foreach from=$group_buckets item=group_bucket_list key=groupId}
					{$group = $groups.$groupId}
					{if isset($active_worker_memberships.$groupId)}
						{foreach from=$group_bucket_list item=bucket}
							<option value="m{$bucket->id}">{$group->name}: {$bucket->name}</option>
						{/foreach}
					{/if}
				{/foreach}
			</optgroup>
			<optgroup label="Set Status" style="font-weight:bold;">
				{if $active_worker->hasPriv('core.ticket.actions.close')}<option value="ac">Close</option>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.spam')}<option value="as">Report Spam</option>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}<option value="ad">Delete</option>{/if}
			</optgroup>
			<optgroup label="Set Owner" style="font-weight:bold;">
				{foreach from=$workers item=worker key=worker_id}
					<option value="o{$worker_id}">{$worker->getName()}</option>
				{/foreach}
			</optgroup>
			<optgroup label="Add Watcher" style="font-weight:bold;">
				{foreach from=$workers item=worker key=worker_id}
					<option value="w{$worker_id}">{$worker->getName()}</option>
				{/foreach}
			</optgroup>
			{if $active_worker->hasPriv('core.ticket.view.actions.merge')}
			<optgroup label="Actions" style="font-weight:bold;">
				<option value="merge">Merge</option>
			</optgroup>
			{/if}
		</select>
		{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" onclick="$('#select{$hash}').val('ac');"><span class="glyphicons glyphicons-circle-ok" style="font-size:12px;color:rgb(0,150,0);"></span></button>{/if}<!--
		-->{if $active_worker->hasPriv('core.ticket.actions.spam')}<button type="button" onclick="$('#select{$hash}').val('as');"><span class="glyphicons glyphicons-ban" style="font-size:12px;"></span></button>{/if}<!--
		-->{if $active_worker->hasPriv('core.ticket.actions.delete')}<button type="button" onclick="$('#select{$hash}').val('ad');"><span class="glyphicons glyphicons-circle-remove" style="font-size:12px;color:rgb(150,0,0);"></span></button>{/if}
	</td>
	<td width="98%" valign="middle">
		<input type="hidden" name="piles_hash[]" value="{$hash}">
		<input type="hidden" name="piles_type[]" value="{$stats[0]}">
		<input type="hidden" name="piles_type_param[]" value="{$stats[4]}">
		<input type="hidden" name="piles_value[]" value="{$stats[1]}">
		<label>{if !empty($stats[4])}{$stats[4]}{else}{$stats[0]}{/if} <span style="color:rgb(0,120,0);" title="{$stats[1]}">{$stats[1]|truncate:76:'...':true}</span> {if !empty($stats[2])}({$stats[2]} hits){/if}</label>
	</td>
</tr>
{if !empty($stats[3]) && is_array($stats[3])} {*$stats[0] == 'domain' && *}
	{foreach from=$stats[3] item=sender key=sender_hash}
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle" style="padding-right:5px;">
			<select name="piles_moveto[]" id="select{$sender_hash}">
				<option value=""></option>
				<optgroup label="Move to..." style="color:rgb(0,150,0);font-weight:bold;">
					{foreach from=$group_buckets item=group_bucket_list key=groupId}
						{$group = $groups.$groupId}
						{if isset($active_worker_memberships.$groupId)}
							{foreach from=$group_bucket_list item=bucket}
								<option value="m{$bucket->id}">{$group->name}: {$bucket->name}</option>
							{/foreach}
						{/if}
					{/foreach}
				</optgroup>
				<optgroup label="Set Status" style="font-weight:bold;">
					{if $active_worker->hasPriv('core.ticket.actions.close')}<option value="ac">Close</option>{/if}
					{if $active_worker->hasPriv('core.ticket.actions.spam')}<option value="as">Report Spam</option>{/if}
					{if $active_worker->hasPriv('core.ticket.actions.delete')}<option value="ad">Delete</option>{/if}
				</optgroup>
				<optgroup label="Set Owner" style="font-weight:bold;">
					{foreach from=$workers item=worker key=worker_id}
						<option value="o{$worker_id}">{$worker->getName()}</option>
					{/foreach}
				</optgroup>
				<optgroup label="Add Watcher" style="font-weight:bold;">
					{foreach from=$workers item=worker key=worker_id}
						<option value="w{$worker_id}">{$worker->getName()}</option>
					{/foreach}
				</optgroup>
				{if $active_worker->hasPriv('core.ticket.view.actions.merge')}
				<optgroup label="Actions" style="font-weight:bold;">
					<option value="merge">Merge</option>
				</optgroup>
				{/if}
			</select>
			{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" onclick="$('#select{$sender_hash}').val('ac');"><span class="glyphicons glyphicons-circle-ok" style="font-size:12px;color:rgb(0,150,0);"></span></button>{/if}<!--
			-->{if $active_worker->hasPriv('core.ticket.actions.spam')}<button type="button" onclick="$('#select{$sender_hash}').val('as');"><span class="glyphicons glyphicons-ban" style="font-size:12px;"></span></button>{/if}<!--
			-->{if $active_worker->hasPriv('core.ticket.actions.delete')}<button type="button" onclick="$('#select{$sender_hash}').val('ad');"><span class="glyphicons glyphicons-circle-remove" style="font-size:12px;color:rgb(150,0,0);"></span></button>{/if}
		</td>
		<td width="98%" valign="middle">
			<div style="margin:0px 0px 0px 20px;">
				<input type="hidden" name="piles_hash[]" value="{$sender_hash}">
				<input type="hidden" name="piles_type[]" value="{$sender[0]}">
				<input type="hidden" name="piles_value[]" value="{$sender[1]}">
				<label>{$sender[0]} <span style="color:rgb(0,120,0);" title="{$sender[1]}">{$sender[1]|truncate:76:'...':true}</span> {if !empty($sender[2])}({$sender[2]} hits){/if}</label><br>
			</div>
		</td>
	</tr>	
	{/foreach}	
{/if}
{/foreach}
</table>
<br>
</div>

<button type="button" onclick="genericAjaxPost('viewAssist{$view_id}','view{$view_id}',null);" style="">Perform selected actions</button>
<button type="button" onclick="$('#{$view_id}_tips').hide().html('');" style="">Do nothing</button>

{else}

There aren't enough tickets in this list for auto-assist to find patterns.<br>
<br>
<button type="button" onclick="$('#{$view_id}_tips').hide().html('');" style="">Do nothing</button><br>

{/if}

</form>
