<form action="{devblocks_url}{/devblocks_url}" method="post" id="viewAssist{$view_id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewAutoAssist">
<input type="hidden" name="view_id" value="{$view_id}">

Sort biggest piles by: 
<label><input type="radio" name="mode" value="subjects" {if $mode!="subjects"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=subjects');"{/if} {if $mode=="subjects"}checked{/if}>Subject Prefix</label>
<label><input type="radio" name="mode" value="senders" {if $mode!="senders"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=senders');"{/if} {if $mode=="senders"}checked{/if}>Senders</label>
<label><input type="radio" name="mode" value="headers" onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=headers');" {if $mode=="headers"}checked{/if}>Headers</label>
<br>
<br>

{if !empty($biggest)}
<div id="{$view_id}_piles" style="display:{if empty($tips)}block{else}none{/if};">
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td align="top" colspan="3">
		<H3 style="font-size:18px;margin:0px;">The biggest piles of common {if $mode=="senders"}senders{elseif $mode=="subjects"}subjects{elseif $mode=="import"}import sources{elseif $mode=="headers"}message headers{/if} in this list are:</H3>
	</td>
</tr>
<tr>
	<td width="0%" nowrap align="center">{if $mode=="senders"}Always{/if}</td>
	<td width="0%" nowrap>Move to:</td>
	<td width="100%">From biggest piles:</td>
</tr>
{foreach from=$biggest item=stats key=hash}
<tr>
	<td width="1%" nowrap="nowrap" align="center">
		{if $mode=="senders"}
		<input type="checkbox" name="piles_always[]" value="{$hash}">
		{/if}
	</td>
	<td width="1%" nowrap="nowrap">
		<select name="piles_moveto[]" id="select{$hash}">
			<option value=""></option>
			<optgroup label="Move to Group" style="color:rgb(0,150,0);font-weight:bold;">
				{foreach from=$teams item=team}
					<option value="t{$team->id}">{$team->name}</option>
				{/foreach}
			</optgroup>
			{foreach from=$team_categories item=team_category_list key=teamId}
				{assign var=team value=$teams.$teamId}
				{if isset($active_worker_memberships.$teamId)}
					<optgroup label="-- {$team->name} --">
					{foreach from=$team_category_list item=category}
						<option value="c{$category->id}">{$category->name}</option>
					{/foreach}
					</optgroup>
				{/if}
			{/foreach}
			<optgroup label="Actions" style="color:rgb(150,0,0);font-weight:bold;">
				<option value="ac">Close</option>
				<option value="as">Report Spam</option>
				{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}<option value="ad">Delete</option>{/if}
			</optgroup>
		</select>
		<a href="javascript:;" onclick="document.getElementById('viewAssist{$view_id}').select{$hash}.value='ac';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top" border="0"></a>
		<a href="javascript:;" onclick="document.getElementById('viewAssist{$view_id}').select{$hash}.value='as';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top" border="0"></a>
		{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}<a href="javascript:;" onclick="document.getElementById('viewAssist{$view_id}').select{$hash}.value='ad';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"></a>{/if}
	</td>
	<td width="98%" align="top">
		<input type="hidden" name="piles_hash[]" value="{$hash}">
		<input type="hidden" name="piles_type[]" value="{$stats[0]}">
		<input type="hidden" name="piles_type_param[]" value="{$stats[4]}">
		<input type="hidden" name="piles_value[]" value="{$stats[1]|escape:"htmlall"}">
		<label>{if !empty($stats[4])}{$stats[4]}{else}{$stats[0]}{/if} <span style="color:rgb(0,120,0);" title="{$stats[1]|escape:"htmlall"}">{$stats[1]|truncate:76:'...':true|escape:"htmlall"}</span> {if !empty($stats[2])}({$stats[2]} hits){/if}</label>
	</td>
</tr>
{if !empty($stats[3]) && is_array($stats[3])} {*$stats[0] == 'domain' && *}
	{foreach from=$stats[3] item=sender key=sender_hash}
	<tr>
		<td width="1%" nowrap="nowrap" align="center">
			{if $mode=="senders"}
			<input type="checkbox" name="piles_always[]" value="{$sender_hash}">
			{/if}
		</td>
		<td width="1%" nowrap="nowrap">
			<select name="piles_moveto[]" id="select{$sender_hash}">
				<option value=""></option>
				<optgroup label="Move to Group" style="color:rgb(0,150,0);font-weight:bold;">
					{foreach from=$teams item=team}
						<option value="t{$team->id}">{$team->name}</option>
					{/foreach}
				</optgroup>
				{foreach from=$team_categories item=team_category_list key=teamId}
					{assign var=team value=$teams.$teamId}
					{if isset($active_worker_memberships.$teamId)}
						<optgroup label="-- {$team->name} --">
						{foreach from=$team_category_list item=category}
							<option value="c{$category->id}">{$category->name}</option>
						{/foreach}
						</optgroup>
					{/if}
				{/foreach}
				<optgroup label="Actions" style="color:rgb(150,0,0);font-weight:bold;">
					<option value="ac">Close</option>
					<option value="as">Report Spam</option>
					<option value="ad">Delete</option>
				</optgroup>
			</select>
			<a href="javascript:;" onclick="document.getElementById('viewAssist{$view_id}').select{$sender_hash}.value='ac';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top" border="0"></a>
			<a href="javascript:;" onclick="document.getElementById('viewAssist{$view_id}').select{$sender_hash}.value='as';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top" border="0"></a>
			{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}<a href="javascript:;" onclick="document.getElementById('viewAssist{$view_id}').select{$sender_hash}.value='ad';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"></a>{/if}
		</td>
		<td width="98%">
			<blockquote style="margin-bottom:0px;">
				<input type="hidden" name="piles_hash[]" value="{$sender_hash}">
				<input type="hidden" name="piles_type[]" value="{$sender[0]}">
				<input type="hidden" name="piles_value[]" value="{$sender[1]|escape:"htmlall"}">
				<label>{$sender[0]} <span style="color:rgb(0,120,0);" title="{$sender[1]|escape:"htmlall"}">{$sender[1]|truncate:76:'...':true|escape:"htmlall"}</span> {if !empty($sender[2])}({$sender[2]} hits){/if}</label><br>
			</blockquote>
		</td>
	</tr>	
	{/foreach}	
{/if}
{/foreach}
</table>
<br>
</div>

<button type="button" onclick="this.form.submit();" style="">Perform selected actions</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');clearDiv('{$view_id}_tips');" style="">Do nothing</button>

{else}

There aren't enough tickets in this list for auto-assist to find patterns.<br>
<br>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');clearDiv('{$view_id}_tips');" style="">Do nothing</button><br>

{/if}

</form>
