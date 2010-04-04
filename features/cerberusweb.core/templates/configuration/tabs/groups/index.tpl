{if (empty($license) || empty($license.workers)) && count($teams) >= 3}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
		<p>
			<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
			<strong>You have reached the free version limit of (3) groups.</strong><br>
			{if empty($license.workers) && count($teams) > 3}<strong>Your database contains ({count($teams)}) groups, but free mode only permits (3).  Please be honest.</strong><br>{/if}
			<a href="{devblocks_url}c=config&a=settings{/devblocks_url}">(upgrade license)</a>
		</p>
	</div>
</div>
{/if}

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
						{if (empty($license) || empty($license.workers)) && count($teams) >= 3}
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


