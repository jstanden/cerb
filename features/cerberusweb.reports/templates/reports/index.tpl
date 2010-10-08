<ul class="submenu">
</ul>
<div style="clear:both;"></div>

{counter assign=num_groups_displayed name=num_groups_displayed start=0}
{if !empty($report_groups)}
	{foreach from=$report_groups item=report_group key=group_extid}
		{assign var=report_group_mft value=$report_group.manifest}
		
		{if !isset($report_group_mft->params.acl) || $active_worker->hasPriv($report_group_mft->params.acl)}
		{counter name=num_groups_displayed print=false}
		<fieldset>
			<legend>{$translate->_($report_group_mft->params.group_name)}</legend>
			
			{if !empty($report_group.reports)}
				<ul style="margin:0px;">
				{foreach from=$report_group.reports item=reportMft}
					<li><a href="{devblocks_url}c=reports&report={$reportMft->id}{/devblocks_url}">{$translate->_($reportMft->params.report_name)}</a></li>
				{/foreach}
				</ul>
			{/if}
		</fieldset>
		{/if}
	{/foreach}
{/if}

{if empty($num_groups_displayed)}
	<div class="block">
		<h3>No Report Groups</h3>
		You do not have access to any report groups.
	</div>
{/if}

