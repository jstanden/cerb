<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>{$translate->_('timetracking.ui.cfg.activities')}</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						[ <a href="javascript:;" onclick="genericAjaxGet('configActivity','c=config&a=handleTabAction&tab=timetracking.config.tab.activities&action=getActivity&id=0');">{$translate->_('timetracking.ui.cfg.add_new_activity')}</a> ]
					</td>
				</tr>
				<tr>
					<td>
						{if !empty($billable_activities)}
						<b>{$translate->_('timetracking.ui.billable_label')}</b><br>
						<div style="margin:0px;padding:3px;width:200px;">
							{foreach from=$billable_activities item=activity}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configActivity','c=config&a=handleTabAction&tab=timetracking.config.tab.activities&action=getActivity&id={$activity->id}');">{$activity->name}</a><br>
							<small>&nbsp; &nbsp; {$translate->_('timetracking.ui.cfg.currency')} {'timetracking.ui.cfg.n_per_hour'|devblocks_translate:$activity->rate}</small><br>
							{/foreach}
						</div>
						{/if}
						
						{if !empty($nonbillable_activities)}
						<b>{$translate->_('timetracking.ui.non_billable_label')}</b><br>
						<div style="margin:0px;padding:3px;width:200px;">
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
				{include file="$path/config/activities/edit_activity.tpl" activity=null}
			</form>
		</td>
		
	</tr>
</table>
