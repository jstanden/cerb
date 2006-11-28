<table class="tableGreen" border="0" cellpadding="2" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td class="tableThGreen" nowrap="nowrap"> <img src="images/folder_network.gif"> Properties</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Status:</b><br>
      	<select name="">
      		<option value="">open
      		<option value="">waiting for reply
      		<option value="">closed
      		<option value="">deleted
      	</select>
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Priority:</b><br>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td align="center"><label for="priority0"><img src="images/star_alpha.gif" width="16" height="16" border="0" title="None" alt="No Priority"></label></td>
					<td align="center"><label for="priority1"><img src="images/star_grey.gif" width="16" height="16" border="0" title="Lowest" alt="Lowest Priority"></label></td>
					<td align="center"><label for="priority2"><img src="images/star_blue.gif" width="16" height="16" border="0" title="Low" alt="Low Priority"></label></td>
					<td align="center"><label for="priority3"><img src="images/star_green.gif" width="16" height="16" border="0" title="Moderate" alt="Moderate Priority"></label></td>
					<td align="center"><label for="priority4"><img src="images/star_yellow.gif" width="16" height="16" border="0" title="High" alt="High Priority"></label></td>
					<td align="center"><label for="priority5"><img src="images/star_red.gif" width="16" height="16" border="0" title="Highest" alt="Highest Priority"></label></td>
				</tr>
				<tr>
					<td align="center"><input id="priority0" type="radio" name="ticket_priority" value="0" checked></td>
					<td align="center"><input id="priority1" type="radio" name="ticket_priority" value="25" ></td>
					<td align="center"><input id="priority2" type="radio" name="ticket_priority" value="50" ></td>
					<td align="center"><input id="priority3" type="radio" name="ticket_priority" value="75" ></td>
					<td align="center"><input id="priority4" type="radio" name="ticket_priority" value="90" ></td>
					<td align="center"><input id="priority5" type="radio" name="ticket_priority" value="100" ></td>
				</tr>
			</table>      	
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Spam Probability:</b><br>
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td style="background-color:rgb(0,200,0);padding:3px;"><b style="color:rgb(255,255,255);">0.00%</b></td>
					<td>Marked as Not Spam</td>
				</tr>
			</table>
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Mailbox:</b><br>
      	<select name="">
      	</select>
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Due:</b><br>
      	<input type="text">
			<a href="javascript:;"><img src="images/icon_calendar.gif" width="16" height="16" border="0" align="absmiddle"></a>
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Subject:</b><br>
      	<input type="text" value="{$ticket->subject|escape:"htmlall"}">
     	</td>
    </tr>
  </tbody>
</table>
