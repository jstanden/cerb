<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h1>Reports</h1>
<br>

{if !empty($report_groups)}
	{foreach from=$report_groups item=report_group key=group_extid}
	<div class="block">
		<h2>{$translate->_($report_group.name)|capitalize}</h2>
		{if !empty($report_group.reports)}
			<ul style="margin-top:0px;">
			{foreach from=$report_group.reports item=reportMft}
				<li><a href="{devblocks_url}c=reports&report={$reportMft->id}{/devblocks_url}">{$translate->_($reportMft->params.report_name)|capitalize}</a></li>
			{/foreach}
			</ul>
		{/if}
	</div>
	<br>
	{/foreach}
{/if}

