<table cellpadding="0" cellspacing="0" style="padding-bottom:5px;">
<tr>
	<td style="padding-right:5px;"><h1>Send Mail</h1></td>
	<td>
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
</tr>
</table>
{if $smarty.const.DEMO_MODE}
<div style="color:red;padding:2px;font-weight:bold;">NOTE: This helpdesk is in Demo Mode and mail will not be sent.</div>
{/if}

<div class="block">
<h2>Message Details</h2>
<form enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="composeMail">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>From:</b></td>
					<td width="100%">
						<select name="team_id">
							{foreach from=$active_worker_memberships item=membership key=group_id}
							<option value="{$group_id}" {if $group_id==$team->id}selected{/if}>{$teams.$group_id->name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>To:</b>&nbsp; </td>
					<td width="100%">
						<input type="text" size="100" name="to" style="width:98%;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Subject:</b></td>
					<td width="100%"><input type="text" size="100" name="subject" style="width:98%;"></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Message:</b></td>
					<td width="100%">
						<textarea name="content" rows="15" cols="80" class="reply" style="width:98%;">{if !empty($signature)}


{$signature}
{/if}
</textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2"><h2>Attachments</h2></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Attachments:</b>&nbsp; </td>
					<td width="100%">
						<input type="file" name="attachment[]" size="45"></input> 
						<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
						<div id="displayReplyAttachments"></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<br>
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
			<button type="button" onclick="document.location='{devblocks_url}c=tickets{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>