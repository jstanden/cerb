{if $invalidDate}
	<div class="error"><b>Invalid date specified.  Please try again.</b></div>
{else}
{if !empty($time_entries)}
		{foreach from=$time_entries item=activity_entry key=activity_id}
		<div class="block">
		<table cellspacing="0" cellpadding="3" border="0">
			<tr>
				<td colspan="6">
				<h2>
				  {if empty($activity_entry.activity_name)}
				  (no activity)
				  {else}
				  {$activity_entry.activity_name}
				  {/if}
				</h2>
				<span style="margin-bottom:10px;"><b>{$activity_entry.total_mins} minutes</b></span>
				</td>
			</tr>
		
			{foreach from=$activity_entry.entries item=time_entry key=time_entry_id}
				{if is_numeric($time_entry_id)}
					{assign var=source_ext_id value=$time_entry.source_extension_id}
					{assign var=source_id value=$time_entry.source_id}
					<tr>
						<td>{$time_entry.log_date|date_format:"%Y-%m-%d"}</td>
						<td>
							<b>{if isset($time_entry.worker_name)}{$time_entry.worker_name}{else}A worker{/if}</b> 
							tracked <b>{$time_entry.mins} min</b>  
							{if isset($activity_entry.activity_name)}on {$activity_entry.activity_name}{else}{/if} 
							{if !empty($time_entry.org_name)}for <b>{$time_entry.org_name}</b>{/if}
							{if !empty($source_ext_id)}
								{assign var=source value=$sources.$source_ext_id}
								{if !empty($source)}<small>(<a href="{$source->getLink($source_id)}">{$source->getLinkText($source_id)}</a>)</small>{/if}
							{/if}
						</td>
					</tr>
					{if !empty($time_entry.notes)}
					<tr>
						<td></td>
						<td><i>{$time_entry.notes}</i></td>
					</tr>
					{/if}
				{/if}
			{/foreach}

	</table>
	</div>
	<br>
	{/foreach}
{/if}

{/if}