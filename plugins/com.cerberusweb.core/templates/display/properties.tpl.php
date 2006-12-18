<input type="hidden" name="c" value="core.module.display">
<input type="hidden" name="a" value="updateProperties">
<input type="hidden" name="id" value="{$ticket->id}">
<table class="tableGreen" border="0" cellpadding="2" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td class="tableThGreen" nowrap="nowrap"> <img src="images/folder_network.gif"> Properties</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Status:</b><br>
      	<select name="status">
      		<option value="O" {if $ticket->status=='O'}selected{/if}>{$translate->say('status.open')|lower}
      		<option value="W" {if $ticket->status=='W'}selected{/if}>{$translate->say('status.waiting')|lower}
      		<option value="C" {if $ticket->status=='C'}selected{/if}>{$translate->say('status.closed')|lower}
      		<option value="D" {if $ticket->status=='D'}selected{/if}>{$translate->say('status.deleted')|lower}
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
					<td align="center"><input id="priority0" type="radio" name="priority" value="0" {if $ticket->priority==0}checked{/if}></td>
					<td align="center"><input id="priority1" type="radio" name="priority" value="25" {if $ticket->priority==25}checked{/if}></td>
					<td align="center"><input id="priority2" type="radio" name="priority" value="50" {if $ticket->priority==50}checked{/if}></td>
					<td align="center"><input id="priority3" type="radio" name="priority" value="75" {if $ticket->priority==75}checked{/if}></td>
					<td align="center"><input id="priority4" type="radio" name="priority" value="90" {if $ticket->priority==90}checked{/if}></td>
					<td align="center"><input id="priority5" type="radio" name="priority" value="100" {if $ticket->priority==100}checked{/if}></td>
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
      	<select name="mailbox_id">
      		{foreach from=$mailboxes item=mailbox name=mailboxes}
      			<option value="{$mailbox->id}" {if $ticket->mailbox_id==$mailbox->id}selected{/if}>{$mailbox->name}
      		{/foreach}
      	</select>
     	</td>
    </tr>
    <!---
    <tr>
      <td>
      	<b class="green">Due:</b><br>
      	<input name="due" type="text">
			<a href="javascript:;"><img src="images/icon_calendar.gif" width="16" height="16" border="0" align="absmiddle"></a>
     	</td>
    </tr>
    --->
    <tr>
      <td>
      	<b class="green">Subject:</b><br>
      	<input type="text" name="subject" value="{$ticket->subject|escape:"htmlall"}" style="width:98%">
     	</td>
    </tr>
    <tr>
    	<td align="right">
    		<input type="submit" value="Update Properties">
    	</td>
    </tr>
  </tbody>
</table>