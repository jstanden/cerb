{if $invalidDate}
	<div class="error"><b>{$translate->_('timetracking.ui.reports.invalid_date')}</b></div>
{else}
{if !empty($time_entries)}
		{foreach from=$time_entries item=activity_entry key=activity_id}
		<div class="block">
		<table cellspacing="0" cellpadding="3" border="0">
			<tr>
				<td colspan="6">
				<h2>
				  {if empty($activity_entry.activity_name)}
				  {$translate->_('timetracking.ui.reports.time_spent_activity.no_activity')}
				  {else}
				  {$activity_entry.activity_name}
				  {/if}
				</h2>
				<span style="margin-bottom:10px;"><b>{$activity_entry.total_mins} {$translate->_('common.minutes')|lower}</b></span>
				</td>
			</tr>
		
			{foreach from=$activity_entry.entries item=time_entry key=time_entry_id}
				{if is_numeric($time_entry_id)}
					{assign var=source_ext_id value=$time_entry.source_extension_id}
					{assign var=source_id value=$time_entry.source_id}
					{assign var=generic_worker value='timetracking.ui.generic_worker'|devblocks_translate}
					
					{if isset($time_entry.worker_name)}
						{assign var=worker_name value=$time_entry.worker_name}
					{else}
						{assign var=worker_name value=$generic_worker}
					{/if}
					<tr>
						<td>{$time_entry.log_date|date_format:"%Y-%m-%d"}</td>
						<td>
							{assign var=tagged_worker_name value="<B>"|cat:$worker_name|cat:"</B>"}
							{assign var=tagged_mins value="<B>"|cat:$time_entry.mins|cat:"</B>"}
							{assign var=tagged_activity value="<B>"|cat:$activity_entry.activity_name|cat:"</B>"}
						
							{if !empty($time_entry.org_name)}
								{assign var=tagged_org_name value="<B>"|cat:$time_entry.org_name|cat:"</B>"}							
								{'timetracking.ui.reports.tracked_desc.with_org'|devblocks_translate:$tagged_worker_name:$tagged_mins:$tagged_activity:$tagged_org_name}
							{else}
								{'timetracking.ui.tracked_desc'|devblocks_translate:$tagged_worker_name:$tagged_mins:$tagged_activity}
							{/if}
						
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
