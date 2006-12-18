<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
			<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBlue">
				<tr>
					<td class="tableThBlue">Teams</td>
				</tr>
				<tr>
					<td style="background-color:rgb(220, 220, 255);"><a href="javascript:;" onclick="configAjax.getTeam('0');">add new team</a></td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;height:150px;width:200px;overflow:auto;">
						{if !empty($teams)}
							{foreach from=$teams item=team}
							&#187; <a href="javascript:;" onclick="configAjax.getTeam('{$team->id}')">{$team->name}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
		</td>
		
		<td width="100%" valign="top">
			<form action="index.php#teams" method="post" id="configTeam">
				{include file="$path/configuration/workflow/edit_team.tpl.php" team=null}
			</form>
		</td>
		
	</tr>
</table>

