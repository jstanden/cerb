<table cellpadding="0" cellspacing="0">
<tr>
	<td style="padding-right:5px;"><h1>Create Ticket</h1></td>
	<td>
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
</tr>
</table>

<form enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="createTicket">

<table cellpadding="2" cellspacing="0" border="0" width="100%" class="displayReplyTable">
  <tbody>
	<tr>
		<th>Create New Ticket</th>
	</tr>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>From:</b></td>
					<td width="100%">
						<select name="from">
							{foreach from=$teams item=team name=team}
								<option value="{$team->id}" {if $smarty.foreach.teams.first}selected{/if}>{$team->name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>To:</b></td>
					<td width="100%"><input type="text" size="100" name="to"></td>
				</tr>
				<tr>
					<td></td>
					<td><!-- {$translate->_('common.prev')|capitalize} -->
						<a href="javascript:;" onclick="toggleDiv('createTicketCC');">Add CC</a>
						<a href="javascript:;" onclick="toggleDiv('createTicketBCC');">Add BCC</a>
					</td>
				</tr>
			  <div id="createTicketCC" style="display: none;">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Cc:</b></td>
					<td width="100%"><textarea rows="2" cols="80" name="cc" class="cc"></textarea></td>
				</tr>
			  </div>
			  <div id="createTicketBCC" style="display: block;">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Bcc:</b></td>
					<td width="100%"><textarea rows="2" cols="80" name="bcc" class="cc"></textarea></td>
				</tr>
			  </div>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Subject:</b></td>
					<td width="100%"><input type="text" size="100" name="subject"></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td><textarea name="content" rows="10" cols="80" class="reply"></textarea></td>
	</tr>
	<tr>
		<td>
			<b>Attachments:</b><br>
			<input type="file" name="attachment[]"></input> 
			<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
			<div id="displayReplyAttachments"></div>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Send">
			<input type="button" value="Discard"  onclick="window.back();">
		</td>
	</tr>
  </tbody>
</table>
</form>