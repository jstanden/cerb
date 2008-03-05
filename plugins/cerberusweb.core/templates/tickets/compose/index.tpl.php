{include file="file:$path/tickets/submenu.tpl.php"}

{if $smarty.const.DEMO_MODE}
<div style="color:red;padding:2px;font-weight:bold;">NOTE: This helpdesk is in Demo Mode and mail will not be sent.</div>
{/if}
{if !empty($last_ticket_mask)}
<div class="success">Message sent! &nbsp; &nbsp; <a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}" style="font-weight:normal;color:rgb(80,80,80);">View the message</a></div>
{/if}

<div class="block">
<h2>Outgoing Message</h2>
<form name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="composeMail">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>From:</b>&nbsp;</td>
					<td width="100%">
						<select name="team_id" id="team_id" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$active_worker_memberships item=membership key=group_id}
							<option value="{$group_id}" {if $group_id==$team->id}selected{/if}>{$teams.$group_id->name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>To:</b>&nbsp;</td>
					<td width="100%">
						<div id="emailautocomplete" style="width:98%;padding-bottom:2em;z-index:1;">
							<input type="text" name="to" id="emailinput" value="{$to}" style="border:1px solid rgb(180,180,180);padding:2px;" autocomplete="off">
							<div id="emailcontainer"></div>
						</div>			
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">Cc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">Bcc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="bcc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>Subject:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>
				<tr>
					<td colspan="2">
						<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.team_id),{literal}function(o){insertAtCursor(document.getElementById('content'),o.responseText);}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Insert Signature</button>
						<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplatesPanel&type=1&reply_id=0&txt_name=content',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="top"> E-mail Templates</button>
						<br>
						<textarea name="content" id="content" rows="15" cols="80" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
					</td>
				</tr>
				
				<tr>
					<td colspan="2">
					<div style="margin:5px;padding:5px;background-color:rgb(235,255,211);">
					<H2>Attachments:</H2>
						<table cellpadding="2" cellspacing="0" border="0">
							<tr>
								<td width="0%" nowrap="nowrap" valign="top"><b>Attachments:</b>&nbsp; </td>
								<td width="100%">
									<input type="file" name="attachment[]" size="45"> 
									<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
									<div id="displayReplyAttachments"></div>
								</td>
							</tr>
						</table>
						</div>
					</td>
				</tr>
				
				<tr>
					<td colspan="2">
						<div style="background-color:rgb(239,245,255);margin:5px;padding:5px;">
						<H2>Next:</H2>
							<table cellpadding="2" cellspacing="0" border="0">
								<tr>
									<td nowrap="nowrap" valign="top" colspan="2">
										<label><input type="radio" name="closed" value="0" onclick="toggleDiv('replyOpen','block');toggleDiv('replyClosed','none');">Open</label>
										<label><input type="radio" name="closed" value="2" onclick="toggleDiv('replyOpen','block');toggleDiv('replyClosed','none');" checked>Waiting for reply</label>
										<label><input type="radio" name="closed" value="1" onclick="toggleDiv('replyOpen','none');toggleDiv('replyClosed','block');">Closed</label>
										<br>
										<br>
				
										<b>Who should handle the follow-up?</b><br>
								      	<select name="next_worker_id">
								      		<option value="0">Anybody
								      		{foreach from=$workers item=worker key=worker_id name=workers}
								      			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
								      			<option value="{$worker_id}">{$worker->getName()}
								      		{/foreach}
								      	</select>&nbsp;
								      	{if !empty($next_worker_id_sel)}
								      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};">me</button>
								      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;">anybody</button>
								      	{/if}
								      	<br>
								      	<br>
				
										<b>What is the next action that needs to happen?</b> (max 255 chars)<br>  
								      	<input type="text" name="next_action" size="80" maxlength="255" value=""><br>
								      	<br>
				
										<div id="replyOpen" style="display:block;">
										<b>Would you like to move this conversation?</b><br>  
								      	<select name="bucket_id">
								      		<option value="">-- no thanks! --</option>
								      		<optgroup label="Inboxes">
								      		{foreach from=$teams item=team}
								      			<option value="t{$team->id}">{$team->name}</option>
								      		{/foreach}
								      		</optgroup>
								      		{foreach from=$team_categories item=categories key=teamId}
								      			{assign var=team value=$teams.$teamId}
								      			<optgroup label="-- {$team->name} --">
								      			{foreach from=$categories item=category}
								    				<option value="c{$category->id}">{$category->name}</option>
								    			{/foreach}
								    			</optgroup>
								     		{/foreach}
								      	</select><br>
								      	<br>
								      	</div>
								      	
								      	<div id="replyClosed" style="display:none;">
								      	<b>When would you like to resume this conversation?</b><br> 
								      	<input type="text" name="ticket_reopen" size="55" value=""><br>
								      	examples: "Friday", "+7 days", "Tomorrow 11:15AM", "Dec 31 2010"<br>
								      	(leave blank to wait for a reply before resuming)<br>
								      	<br>
								      	</div>
				
									</td>
								</tr>
							</table>
							</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
			<button type="button" onclick="document.location='{devblocks_url}c=tickets{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	ajax.cbEmailPeek(null);
});
{/literal}
</script>