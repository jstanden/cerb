{include file="$path/crm/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Opportunities</h1>
	</td>
	<td width="99%" valign="middle">
		overview
		 | 	
		<a href="{devblocks_url}c=crm&a=opps&s=search{/devblocks_url}">search</a>
	</td>
</tr>
</table>

{if !empty($campaigns)}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="crm">
	<input type="hidden" name="a" value="">
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppPanel&id=0&view_id={$view->id}',this,false,'500px',ajax.cbEmailPeek);"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/money.gif{/devblocks_url}" align="top"> Add Opportunity</button>
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppImportPanel&id=0',null,true,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/document_into.gif{/devblocks_url}" align="top"> Import</button>
</form>
{/if}

<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
	
		{if !empty($unassigned_totals)}
		<div style="width:220px;">
			<div class="block">
				<h2>Available</h2>
				<a href="{devblocks_url}c=crm&a=opps&o=overview&m=all{/devblocks_url}">-All-</a><br>
				{foreach from=$campaigns item=campaign key=campaign_id}
					{assign var=campaign_totals value=$unassigned_totals.$campaign_id}
					{if $unassigned_totals.$campaign_id}
						<a href="javascript:;" onclick="toggleDiv('campaignBuckets{$campaign_id}');" style="font-weight:bold;">{$campaign->name}</a> ({$campaign_totals.total})<br>

						<div id="campaignBuckets{$campaign_id}" style="display:{if $filter_campaign_id==$campaign_id}block{else}none{/if};padding-left:10px;padding-bottom:2px;">
							<a href="{devblocks_url}c=crm&a=opps&o=overview&m=campaign&id={$campaign_id}{/devblocks_url}">-All-</a><br>
							
							{if !empty($campaign_totals.0)}
								<a href="{devblocks_url}c=crm&a=opps&o=overview&m=campaign&id={$campaign_id}&bid=0{/devblocks_url}">Inbox</a> ({$campaign_totals.0})<br>
							{/if}
							
							{foreach from=$campaign_buckets.$campaign_id item=bucket key=bucket_id}
								{assign var=campaign_bucket value=$campaign_buckets.$campaign_id.$bucket_id}
								{if !empty($campaign_totals.$bucket_id)}
									<a href="{devblocks_url}c=crm&a=opps&o=overview&m=campaign&id={$campaign_id}&bid={$bucket_id}{/devblocks_url}">{$campaign_bucket->name}</a> ({$campaign_totals.$bucket_id})<br>
								{/if}
							{/foreach}
						</div>
					{/if}
				{/foreach}
			</div>
			<br>
			{/if}
		
			{if !empty($assigned_totals)}
			<div class="block">
				<h2>Assigned</h2>
				{foreach from=$workers item=worker key=worker_id}
					{if $assigned_totals.$worker_id}
						<a href="{devblocks_url}c=crm&a=opps&o=overview&m=worker&id={$worker_id}{/devblocks_url}">{$worker->getName()}</a> ({$assigned_totals.$worker_id})<br>
					{/if}
				{/foreach}
			</div>
			{/if}
		</div>
		
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>

</table>