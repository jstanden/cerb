<table cellpadding="0" cellspacing="0" style="padding-bottom:5px;">
<tr>
	<td style="padding-right:5px;"><h1>Log New Ticket</h1></td>
	<td>
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
</tr>
</table>

<div class="block">
<h2>Ticket Details</h2>
<form enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="createTicket">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>For:</b>&nbsp; </td>
					<td width="100%">
						<input type="hidden" name="team_id" value="{$team->id}">
						{$team->name}
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Contact E-mail:</b></td>
					<td width="100%"><input type="text" size="100" name="from"></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Subject:</b></td>
					<td width="100%"><input type="text" size="100" name="subject"></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Message:</b></td>
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
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			<button type="button" onclick="document.location='{devblocks_url}c=tickets{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>