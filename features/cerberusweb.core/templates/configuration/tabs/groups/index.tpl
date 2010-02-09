<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Groups</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						{* [WGM]: Please respect our licensing and support the project! *}
						{if (empty($license) || empty($license.serial)) && count($teams) >= 3}
						You have reached your Cerberus Helpdesk free version limit of 3 groups.<br>
						[ <a href="{devblocks_url}c=config&a=settings{/devblocks_url}" style="color:rgb(0,160,0);">Enter License</a> ]
						[ <a href="http://www.cerberusweb.com/buy" target="_blank" style="color:rgb(0,160,0);">Buy License</a> ]
						{else}
						[ <a href="javascript:;" onclick="genericAjaxGet('configTeam','c=config&a=getTeam&id=0');">add new group</a> ]
						{/if}
					</td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						{if !empty($teams)}
							{foreach from=$teams item=team key=team_id}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configTeam','c=config&a=getTeam&id={$team->id}');">{$team->name}</a><br>
							{/foreach}
						{/if}
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configTeam">
				{include file="$core_tpl/configuration/tabs/groups/edit_group.tpl" team=null}
			</form>
		</td>
		
	</tr>
</table>


