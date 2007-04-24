<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td class="configTableTh">Helpdesk Settings</td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveSettings">
			
			<b>Helpdesk Title:</b><br>
			<input type="text" name="title" value="{$settings->get('helpdesk_title')|escape:"html"}" size="64"><br>
			<br>
			
			<b>Timezone:</b><br>
			<select name="timezone">
				<option value="">---</option>
			</select><br>
			<br>
			
			<input type="submit" value="Save Changes">
			</form>
		</td>
	</tr>
</table>
