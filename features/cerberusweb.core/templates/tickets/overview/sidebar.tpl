{if empty($group_counts) && empty($waiting_counts) && empty($worker_groups)}
<div class="block">
<h2>{$translate->_('mail.overview.all_done')}</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td>{$translate->_('mail.overview.all_done_text')}</td>
	</tr>
</table>
</div>
<br>
{/if}

<div id="tourOverviewSummaries"></div>
    
{if !empty($group_counts)}
<div class="block">
<table cellspacing="0" cellpadding="2" border="0" width="220">
<tr>
	<td><h2>{$translate->_('status.open')|capitalize}</h2></td>
</tr>
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$group_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=overview&s=group&gid={$group_id}{/devblocks_url}" style="font-weight:bold;">{$groups.$group_id->name}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span> 
				<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
				{if !empty($counts.0)}<a href="{devblocks_url}c=tickets&a=overview&s=group&gid={$group_id}&bid=0{/devblocks_url}">{$translate->_('common.inbox')|capitalize}</a> <span style="color:rgb(150,150,150);">({$counts.0})</span><br>{/if}
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
					{if !empty($counts.$bucket_id)}	<a href="{devblocks_url}c=tickets&a=overview&s=group&gid={$group_id}&bid={$bucket_id}{/devblocks_url}">{$b->name}</a> <span style="color:rgb(150,150,150);"> ({$counts.$bucket_id})</span><br>{/if}
				{/foreach}
				</div>
			</td>
		</tr>
	{/if}
{/foreach}
<tr>
	<td>
		<div style="display:none;visibility:hidden;">
			<button id="btnOverviewListAll" onclick="document.location='{devblocks_url}c=tickets&a=overview&all=all{/devblocks_url}';"></button>
		</div>
		<div style="margin-top:2px;color:rgb(150,150,150);">
			(<b>a</b>) <a href="javascript:;" onclick="document.getElementById('btnOverviewListAll').click();" style="color:rgb(150,150,150);">{$translate->_('mail.overview.all_groups')|lower}</a>
		</div>
	</td>
</tr>
</table>
</div>
<br>
{/if}

<div id="tourOverviewWaiting"></div>
{if !empty($waiting_counts)}
<div class="block">
<h2>{$translate->_('mail.waiting')|capitalize}</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
<!-- 
<tr>
	<td><a href="{devblocks_url}c=tickets&a=overview&all=all{/devblocks_url}">{$translate->_('common.all')|capitalize}</a></td>
	<td></td>
</tr>
 -->
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$waiting_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=overview&s=waiting&gid={$group_id}{/devblocks_url}" style="font-weight:bold;">{$groups.$group_id->name}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
			</td>
		</tr>
	{/if}
{/foreach}
</table>
</div>
<br>
{/if}

{if !empty($worker_counts)}
<div class="block">
<h2>{$translate->_('common.assigned')|capitalize}</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=overview&s=worker&wid={$worker_id}{/devblocks_url}" style="font-weight:bold;{if $worker_id==$active_worker->id}color:rgb(255,50,50);background-color:rgb(255,213,213);{/if}">{$workers.$worker_id->getName()}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
				<div style="display:none;padding-left:10px;padding-bottom:0px;">
					{foreach from=$counts item=team_hits key=team_id}
						{if is_numeric($team_id)}
							<a href="{devblocks_url}c=tickets&a=overview&s=worker&wid={$worker_id}&gid={$team_id}{/devblocks_url}">{$groups.$team_id->name}</a> <span style="color:rgb(150,150,150);">({$team_hits})</span><br>
						{/if}
					{/foreach}
				</div>
			</td>
		</tr>
		{/if}
	{/foreach}
<tr>
	<td>
		<div style="display:none;visibility:hidden;">
			<button id="btnMyTickets" onclick="document.location='{devblocks_url}c=tickets&a=overview&worker=worker&id={$active_worker->id}{/devblocks_url}';"></button>
		</div>
		<div style="margin-top:2px;color:rgb(150,150,150);"> 
			(<b>m</b>) <a href="javascript:;" onclick="document.getElementById('btnMyTickets').click();" style="color:rgb(150,150,150);">{$translate->_('mail.overview.my_mail')|lower}</a>
		</div>
	</td>
</tr>
</table>
</div>
<br>
{/if}
