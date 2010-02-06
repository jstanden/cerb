{include file="file:$core_tpl/tickets/submenu.tpl"}

{if $smarty.const.DEMO_MODE}
<div style="color:red;padding:2px;font-weight:bold;">NOTE: This helpdesk is in Demo Mode and mail will not be sent.</div>
{/if}
{if !empty($last_ticket_mask)}
<div class="success">Message created! &nbsp; &nbsp; <a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}" style="font-weight:normal;color:rgb(80,80,80);">View the message</a></div>
{/if}

<div class="block">
<h2>{$translate->_('mail.log_message')|capitalize}</h2>
<form name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}" onsubmit="return ('1' == this.do_submit.value);">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="logTicket">
<input type="hidden" name="do_submit" value="0">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.to'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<select name="to" id="to" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$destinations item=destination}
							<option value="{$destination}" {if 0==strcasecmp($destination,$to)}selected{/if}>{$destination}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'mail.log_message.requesters'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="reqs" id="emailinput" value="{$reqs}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.subject'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>
		<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.to),function(text) { insertAtCursor(document.getElementById('content'), text); } );"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Insert Signature</button>
		<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplatesPanel&type=3&txt_name=content',null,false,'550');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="top"> E-mail Templates</button>
		{* Plugin Toolbar *}
		{if !empty($logmail_toolbaritems)}
			{foreach from=$logmail_toolbaritems item=renderer}
				{if !empty($renderer)}{$renderer->render($message)}{/if}
			{/foreach}
		{/if}
		<br>
		
		<div id="logTicketToolbarOptions"></div>
		
		<textarea name="content" id="content" rows="15" cols="80" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
		<label><input type="checkbox" name="send_to_requesters" value="1"> {'mail.log_message.send_to_requesters'|devblocks_translate}</label>
		</td>
	</tr>
				
	<tr>
		<td>
			<div id="replyAttachments{$message->id}" style="display:block;margin:5px;padding:5px;background-color:rgb(240,240,240);">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(0,184,4);width:10px;"></td>
				<td style="padding-left:5px;">
					<H2>{$translate->_('display.convo.attachments_label')|capitalize}</H2>
					{'display.reply.attachments_limit'|devblocks_translate:$upload_max_filesize}<br>
					
					<b>{$translate->_('display.reply.attachments_add')}</b> 
					(<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('display.reply.attachments_more')|lower}</a>)
					(<a href="javascript:;" onclick="clearDiv('displayReplyAttachments');appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('common.clear')|lower}</a>)
					<br>
					<table cellpadding="2" cellspacing="0" border="0" width="100%">
						<tr>
							<td width="100%" valign="top">
								<div id="displayReplyAttachments">
									<input type="file" name="attachment[]" size="45"></input><br> 
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			</table>
			</div>
		</td>
	</tr>
				
	<tr>
		<td>
		<div style="background-color:rgb(240,240,240);margin:5px;padding:5px;">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(18,147,195);width:10px;"></td>
				<td style="padding-left:5px;">
				<H2>{$translate->_('display.reply.next_label')|capitalize}</H2>
					<table cellpadding="2" cellspacing="0" border="0">
						<tr>
							<td nowrap="nowrap" valign="top" colspan="2">
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');">{$translate->_('status.open')|capitalize}</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" checked>{$translate->_('status.waiting')|capitalize}</label>
								{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');">{$translate->_('status.closed')|capitalize}</label>{/if}
								<br>
								<br>
		
								<div id="ticketClosed" style="display:block;margin-left:10px;">
								<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
								<input type="text" name="ticket_reopen" size="55" value=""><br>
								{$translate->_('display.reply.next.resume_blank')}<br>
								<br>
								</div>
		
								<b>{$translate->_('display.reply.next.handle_reply')}</b><br>
						      	<select name="next_worker_id" onchange="toggleDiv('replySurrender{$message->id}',this.selectedIndex?'block':'none');">
						      		<option value="0" selected="selected">{$translate->_('common.anybody')|capitalize}
						      		{foreach from=$workers item=worker key=worker_id name=workers}
										{if $worker_id==$active_worker->id || $active_worker->hasPriv('core.ticket.actions.assign')}
							      			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
							      			<option value="{$worker_id}">{$worker->getName()}
										{/if}
						      		{/foreach}
						      	</select>&nbsp;
						      	{if $active_worker->hasPriv('core.ticket.actions.assign') && !empty($next_worker_id_sel)}
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};toggleDiv('replySurrender{$message->id}','block');">{$translate->_('common.me')|lower}</button>
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;toggleDiv('replySurrender{$message->id}','none');">{$translate->_('common.anybody')|lower}</button>
						      	{/if}
						      	<br>
						      	<br>
						      	
						      	<div id="replySurrender{$message->id}" style="display:none;margin-left:10px;">
									<b>{$translate->_('display.reply.next.handle_reply_after')}</b> {$translate->_('display.reply.next.handle_reply_after_eg')}<br>  
							      	<input type="text" name="unlock_date" size="32" maxlength="255" value="">
							      	<button type="button" onclick="this.form.unlock_date.value='+2 hours';">{$translate->_('display.reply.next.handle_reply_after_2hrs')}</button>
							      	<br>
							      	<br>
							    </div>
		
								{if $active_worker->hasPriv('core.ticket.actions.move')}
								<b>{$translate->_('display.reply.next.move')}</b><br>  
						      	<select name="bucket_id">
						      		<option value="">-- {$translate->_('display.reply.next.move.no_thanks')|lower} --</option>
						      		<optgroup label="{$translate->_('common.inboxes')|capitalize}">
						      		{foreach from=$teams item=team}
						      			<option value="t{$team->id}">{$team->name}</option>
						      		{/foreach}
						      		</optgroup>
						      		{foreach from=$team_categories item=categories key=teamId}
										{if !empty($active_worker_memberships.$teamId)}
							      			{assign var=team value=$teams.$teamId}
							      			<optgroup label="-- {$team->name} --">
							      			{foreach from=$categories item=category}
							    				<option value="c{$category->id}">{$category->name}</option>
							    			{/foreach}
							    			</optgroup>
										{/if}
						     		{/foreach}
						      	</select><br>
						      	<br>
								{/if}
						      	
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</div>
		</td>
	</tr>
	
	<tr>
		<td>
			<br>
			<button type="button" onclick="this.form.do_submit.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
			<button type="button" onclick="document.location='{devblocks_url}c=tickets{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script language="JavaScript1.2" type="text/javascript">
	$(function() {
		ajax.emailAutoComplete('#emailinput', { multiple: true } );
	} );
</script>
