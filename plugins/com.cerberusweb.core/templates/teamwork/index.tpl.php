<h1>Teamwork</h1>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<table cellpadding="2" cellspacing="0" border="0%" width="150" class='tableGreen'>	
				<tr class="tableThGreen">
					<th nowrap="nowrap"><img src="images/folder_gear.gif"> My Teams <a href="#" class="normalLink">reload</a>&nbsp;</th>
				</tr>
				<tr style="background-color:rgb(240,240,240);">
					<td nowrap="nowrap"><b style="color:rgb(229,95,0);">Assign me work from:</b></td>
				</tr>
				
				{foreach from=$teams item=team name=teams}
				<tr style="background-color:rgb(240,240,240);">
					<td nowrap="nowrap">
						<label><input type="checkbox"> <img src="images/businessmen.gif" border="0"> 
						<b>{$team->name}</b></label> (0)
					</td>
				</tr>
				{/foreach}
				
				<tr style="background-color:rgb(240,240,240);">
					<td nowrap="nowrap"><b style="color:rgb(229,95,0);">How many tickets?</b></td>
				</tr>
				<tr style="background-color:rgb(240,240,240);">
					<td nowrap="nowrap">
						<select name="">
							<option value="1">1
							<option value="5" selected>5
							<option value="10">10
							<option value="25">25
						</select><input type="button" value="Assign">
					</td>
				</tr>
			</table>
		</td>
		<td width="0%" nowrap="nowrap" valign="top"><img src="images/spacer.gif" width="5" height="1"></td>
		<td width="100%" valign="top">
			[[ list of flagged tickets ]]
		</td>
	</tr>
</table>