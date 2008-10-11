<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Activities</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						[ <a href="javascript:;" onclick="genericAjaxGet('configActivity','c=config&a=handleTabAction&tab=timetracking.config.tab.activities&action=getActivity&id=0');">add new activity</a> ]
					</td>
				</tr>
				<tr>
					<td>
						{if !empty($billable_activities)}
						<b>Billable:</b><br>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
							{foreach from=$billable_activities item=activity}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configActivity','c=config&a=handleTabAction&tab=timetracking.config.tab.activities&action=getActivity&id={$activity->id}');">{$activity->name}</a><br>
							<small>&nbsp; &nbsp; US$ {$activity->rate} /hour</small><br>
							{/foreach}
						</div>
						{/if}
						
						{if !empty($nonbillable_activities)}
						<b>Non-Billable:</b><br>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
							{foreach from=$nonbillable_activities item=activity}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configActivity','c=config&a=handleTabAction&tab=timetracking.config.tab.activities&action=getActivity&id={$activity->id}');">{$activity->name}</a><br>
							{/foreach}
						</div>
						{/if}
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configActivity">
				{include file="$path/config/activities/edit_activity.tpl.php" activity=null}
			</form>
		</td>
		
	</tr>
</table>
