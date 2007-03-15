<table cellpadding="0" cellspacing="0" border="0" class="tableGreen" width="220" class="tableBg">
	<tr>
		<td class="tableThGreen" nowrap="nowrap"> <img src="{devblocks_url}images/gear.gif{/devblocks_url}"> {$translate->say('dashboard.teamwork')|capitalize}</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="0" cellspacing="1" border="0" width="100%" class="tableBg">
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						<img src="{devblocks_url}images/businessmen.gif{/devblocks_url}"> <a href="javascript:;" onclick="ajax.showTeamPanel(this);">{$translate->say('dashboard.team_loads')|capitalize}</a><br>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						<img src="{devblocks_url}images/folder_network.gif{/devblocks_url}"> <a href="javascript:;" onclick="ajax.showMailboxPanel(this);">{$translate->say('dashboard.mailbox_loads')|capitalize}</a><br>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						<img src="{devblocks_url}images/folder_gear.gif{/devblocks_url}"> <a href="javascript:;" onclick="ajax.showAssignPanel(this);">{$translate->say('dashboard.assign_work')|capitalize}</a><br>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
