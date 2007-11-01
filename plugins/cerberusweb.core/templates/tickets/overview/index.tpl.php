<div style="position: relative; width:100%; height: 30;">
	<span style="position: absolute; left: 0; top:0;"><h1 style="display:inline;">Overview</h1>&nbsp;
		{include file="file:$path/tickets/menu.tpl.php"}
	</span>
	<span style="position: absolute; right: 0; top:0;">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>Sender</option>
			<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>Ticket ID</option>
			<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>Subject</option>
			<option value="content"{if $quick_search_type eq 'content'}selected{/if}>Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</span>
</div>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" nowrap="nowrap" valign="top">
   		{if !empty($group_counts)}
      	<div class="block">
		<h2>Unassigned</h2>
		<table cellspacing="0" cellpadding="2" border="0" width="220">
		<tr>
			<td><a href="{devblocks_url}c=tickets&a=overview{/devblocks_url}">All</a></td>
			<td></td>
		</tr>
		{foreach from=$groups key=group_id item=group}
			{assign var=counts value=$group_counts.$group_id}
			{if !empty($counts.total)}
				<tr>
					<td style="padding-right:20px;" nowrap="nowrap" valign="top">
						<a href="javascript:;" onclick="toggleDiv('expandGroup{$group_id}');" style="font-weight:bold;">{$groups.$group_id->name}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
						<div id="expandGroup{$group_id}" style="display:{if $filter_group_id==$group_id}block{else}none{/if};padding-left:10px;padding-bottom:0px;">
						<a href="{devblocks_url}c=tickets&a=overview&s=group&gid={$group_id}{/devblocks_url}">- All -</a><br>
						{if !empty($counts.0)}<a href="{devblocks_url}c=tickets&a=overview&s=group&gid={$group_id}&bid=0{/devblocks_url}">Inbox</a> <span style="color:rgb(150,150,150);">({$counts.0})</span><br>{/if}
						{foreach from=$group_buckets.$group_id key=bucket_id item=b}
							{if !empty($counts.$bucket_id)}	<a href="{devblocks_url}c=tickets&a=overview&s=group&gid={$group_id}&bid={$bucket_id}{/devblocks_url}">{$b->name}</a> <span style="color:rgb(150,150,150);"> ({$counts.$bucket_id})</span><br>{/if}
						{/foreach}
						</div>
					</td>
					<td valign="top"> &nbsp; </td>
				</tr>
			{/if}
		{/foreach}
		</table>
		</div>
		<br>
		{/if}

		{if !empty($worker_counts)}
		<div class="block">
		<h2>Assigned</h2>
		<table cellspacing="0" cellpadding="2" border="0" width="220">
			{foreach from=$workers item=worker key=worker_id}
				{if !empty($worker_counts.$worker_id)}
				{assign var=counts value=$worker_counts.$worker_id}
				<tr>
					<td style="padding-right:20px;" nowrap="nowrap" valign="top">
						<!-- [<a href="javascript:;" onclick="toggleDiv('expandWorker{$worker_id}');">+</a>] --> 
						<a href="{devblocks_url}c=tickets&a=overview&s=worker&wid={$worker_id}{/devblocks_url}" style="font-weight:bold;">{$workers.$worker_id->getName()}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
						<div id="expandWorker{$worker_id}" style="display:none;padding-left:10px;padding-bottom:0px;">
							{foreach from=$counts item=team_hits key=team_id}
								{if is_numeric($team_id)}
									<a href="{devblocks_url}c=tickets&a=overview&s=worker&wid={$worker_id}&gid={$team_id}{/devblocks_url}">{$groups.$team_id->name}</a> <span style="color:rgb(150,150,150);">({$team_hits})</span><br>
								{/if}
							{/foreach}
						</div>
					</td>
					<td valign="top"></td>
				</tr>
				{/if}
			{/foreach}
		</table>
		</div>
		<br>
		{/if}
			
		{if !empty($sla_counts) && count($sla_counts) > 1}
		<div class="block">
		<h2>Service Levels</h2>
		<table cellspacing="0" cellpadding="2" border="0" width="220">
			{foreach from=$sla_counts item=counts key=sla_id}
				{if is_numeric($sla_id)}
				<tr>
					<td style="padding-right:20px;" nowrap="nowrap" valign="top">
						<!-- [<a href="javascript:;" onclick="toggleDiv('expandWorker{$worker_id}');">+</a>] --> 
						<a href="{devblocks_url}c=tickets&a=overview&s=sla&sid={$sla_id}{/devblocks_url}" style="font-weight:bold;">{$slas.$sla_id->name}</a> <span style="color:rgb(150,150,150);">({$counts})</span>
					</td>
					<td valign="top"></td>
				</tr>
				{/if}
			{/foreach}
		</table>
		</div>
		<br>
		{/if}
		
      </td>
      <td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
      <td width="100%" valign="top">
	      {foreach from=$views item=view name=views}
	      	<div id="view{$view->id}">
		      	{$view->render()}
		    </div>
	      {/foreach}
	      
	      {include file="file:$path/tickets/whos_online.tpl.php"}
      </td>
      
    </tr>
  </tbody>
</table>

