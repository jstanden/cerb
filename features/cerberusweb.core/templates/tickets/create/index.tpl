{include file="devblocks:cerberusweb.core::tickets/submenu.tpl"}

{if !empty($last_ticket_mask)}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
		<strong>Message created!</strong> 
		(<a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}">view</a>)
		</p>
	</div>
</div>
{/if}

<div class="block">
<h2>{$translate->_('mail.log_message')|capitalize}</h2>
<form id="frmLogTicket" name="frmLogTicket" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="logTicket">
<input type="hidden" name="draft_id" value="{$draft->id}">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.to'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<select name="to" id="to" class="required" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$destinations item=destination}
							<option value="{$destination->email}" {if 0==strcasecmp($destination->email,$draft->params.to)}selected="selected"{/if}>{$destination->email}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'mail.log_message.requesters'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="reqs" value="{$draft->params.requesters}" class="required" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.subject'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" value="{$draft->subject}" class="required" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>
		<button id="btnSaveDraft" type="button" onclick="genericAjaxPost('frmLogTicket',null,'c=tickets&a=saveDraft&type=create',function(json) { var obj = $.parseJSON(json); if(!obj || !obj.html || !obj.draft_id) return; $('#divDraftStatus').html(obj.html); $('#frmLogTicket input[name=draft_id]').val(obj.draft_id); } );"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> Save Draft</button>
		<button type="button" id="btnInsertSig" title="(Ctrl+Shift+G)" onclick="genericAjaxGet('','c=tickets&a=getLogTicketSignature&email='+escape(selectValue(this.form.to)),function(text) { insertAtCursor(document.getElementById('content'), text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
		<button type="button" onclick="openSnippetsChooser(this);"><span class="cerb-sprite sprite-text_rich"></span> {$translate->_('common.snippets')|capitalize}</button>
		{* Plugin Toolbar *}
		{if !empty($logmail_toolbaritems)}
			{foreach from=$logmail_toolbaritems item=renderer}
				{if !empty($renderer)}{$renderer->render($message)}{/if}
			{/foreach}
		{/if}
		<br>
		
		<div id="logTicketToolbarOptions"></div>
		<div id="divDraftStatus"></div>
		
		<textarea name="content" id="content" rows="15" cols="80" class="reply required" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">{$draft->body}</textarea><br>
		{*<label><input type="checkbox" name="send_to_requesters" value="1" {if $draft->params.send_to_reqs}checked="checked"{/if}> {'mail.log_message.send_to_requesters'|devblocks_translate}</label>*}
		</td>
	</tr>
				
	<tr>
		<td>
			<div id="replyAttachments{$message->id}" style="display:block;margin:5px;padding:5px;background-color:rgb(240,240,240);">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(0,184,4);width:10px;"></td>
				<td style="padding-left:5px;">
					<H2>{$translate->_('common.attachments')|capitalize}:</H2>
					{'display.reply.attachments_limit'|devblocks_translate:$upload_max_filesize}<br>
					
					<b>{$translate->_('display.reply.attachments_add')}</b> 
					(<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('display.reply.attachments_more')|lower}</a>)
					(<a href="javascript:;" onclick="$('#displayReplyAttachments').html('');appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('common.clear')|lower}</a>)
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
		
								{if $active_worker->hasPriv('core.ticket.actions.assign')}
									<b>{$translate->_('display.reply.next.handle_reply')}</b><br>
									<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
							      	<br>
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
			<button type="submit" onclick="$('#btnSaveDraft').click();"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> Send Message</button>
			<button type="button" onclick="$('#btnSaveDraft').click();document.location='{devblocks_url}c=tickets{/devblocks_url}';"><span class="cerb-sprite sprite-media_pause"></span> {$translate->_('display.ui.continue_later')|capitalize}</button>
			<button type="button" onclick="if(confirm('Are you sure you want to discard this message?')) { if(0!==this.form.draft_id.value.length) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id='+escape(this.form.draft_id.value)); } document.location='{devblocks_url}c=tickets{/devblocks_url}'; } "><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('display.ui.discard')|capitalize}</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script type="text/javascript">
	$(function() {
		ajax.emailAutoComplete('#frmLogTicket input[name=reqs]', { multiple: true } );
		
		$('#frmLogTicket').validate();
		
		setInterval("$('#btnSaveDraft').click();", 30000);
		
		$('#frmLogTicket button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
		});

		{if $pref_keyboard_shortcuts}
		
		// Reply textbox
		$('textarea#content').keypress(function(event) {
			if(!$(this).is(':focus'))
				return;
			
			if(!event.ctrlKey) //!event.altKey && !event.ctrlKey && !event.metaKey
				return;
			
			event.preventDefault();

			if(event.ctrlKey && event.shiftKey) {
				switch(event.which) {
					case 7:  // (G) Insert Signature
						try {
							$('#btnInsertSig').click();
						} catch(ex) { } 
						break;
//					case 9:  // (I) Insert Snippet
//						try {
//							$('#reply{$message->id}_part1').find('.context-snippet').focus();
//						} catch(ex) { } 
//						break;
				}
			}
		});
		
		{/if}		
	});
	
	function openSnippetsChooser(button) {
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.snippet&contexts=cerberusweb.contexts.worker',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			event.stopPropagation();
			$button = $(button);
			$textarea = $('#content');
			
			for(idx in event.labels) {
				value = event.values[idx];
				valueParts = value.split('::');
				
				if(null == valueParts || null == valueParts[0] || null == valueParts[1])
					continue;
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				url = 'c=internal&a=snippetPaste&id='+valueParts[0];
				
				// Context-dependent arguments
				if ('cerberusweb.contexts.worker'==valueParts[1]) {
					url += "&context_id={$active_worker->id}";
				}
				
				// Ajax the content (synchronously)
				genericAjaxGet('',url,function(txt) {
					$textarea.insertAtCursor(txt);
				}, { async: false });
			}
		});
	}	
</script>
