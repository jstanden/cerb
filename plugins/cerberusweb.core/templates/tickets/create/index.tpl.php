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

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Destination:</b>&nbsp; </td>
					<td width="100%">
						<select name="team_id">
							{foreach from=$teams item=team name=team}
								<option value="{$team->id}">{$team->name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Sender:</b></td>
					<td width="100%"><input type="text" size="100" name="from"></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Subject:</b></td>
					<td width="100%"><input type="text" size="100" name="subject"></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Body:</b></td>
					<td width="100%">
						<textarea name="content" rows="10" cols="80" class="reply"></textarea>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Attachments:</b>&nbsp; </td>
					<td width="100%">
						<input type="file" name="attachment[]"></input> 
						<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
						<div id="displayReplyAttachments"></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Send"> 
			<a href="{devblocks_url}c=tickets&a=create{/devblocks_url}">Discard</a>
		</td>
	</tr>
  </tbody>
</table>
</form>