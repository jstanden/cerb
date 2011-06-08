{include file="devblocks:cerberusweb.core::tickets/submenu.tpl"}

{if !empty($last_ticket_mask)}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
		<strong>Message sent!</strong> 
		(<a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}">view</a>)
		</p>
	</div>
</div>
{/if}

<div class="block">
<h2>Outgoing Message</h2>
<form id="frmCompose" name="compose" enctype="multipart/form-data" method="POST" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="composeMail">
<input type="hidden" name="draft_id" value="{$draft->id}">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>From:</b>&nbsp;</td>
					<td width="100%">
						<select name="team_id" id="team_id" class="required" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$active_worker_memberships item=membership key=group_id}
							<option value="{$group_id}" {if $group_id==$draft->params.group_id}selected{/if}>{$teams.$group_id->name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>To:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="to" value="{if !empty($draft)}{$draft->params.to}{else}{$defaults_to}{/if}" class="required" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">Cc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="cc" value="{$draft->params.cc}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">Bcc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="bcc" value="{$draft->params.bcc}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>Subject:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" value="{$draft->subject}" class="required" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>

			</table>
		</td>
	</tr>
	
	<tr>
		<td>
			<div>
				<fieldset style="display:inline-block;">
					<legend>Actions</legend>
					<button id="btnSaveDraft" type="button" onclick="genericAjaxPost('frmCompose',null,'c=tickets&a=saveDraft&type=compose',function(json) { var obj = $.parseJSON(json); if(!obj || !obj.html || !obj.draft_id) return; $('#divDraftStatus').html(obj.html); $('#frmCompose input[name=draft_id]').val(obj.draft_id); } );"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> Save Draft</button>
					<button type="button" id="btnInsertSig" title="(Ctrl+Shift+G)" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.team_id),function(text) { insertAtCursor(document.getElementById('content'),text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
					{* Plugin Toolbar *}
					{if !empty($sendmail_toolbaritems)}
						{foreach from=$sendmail_toolbaritems item=renderer}
							{if !empty($renderer)}{$renderer->render($message)}{/if}
						{/foreach}
					{/if}
				</fieldset>
				
				<fieldset style="display:inline-block;">
					<legend>{'common.snippets'|devblocks_translate|capitalize}</legend>
					<div>
						Insert: 
						<input type="text" size="25" class="context-snippet autocomplete">
						<button type="button" onclick="openSnippetsChooser(this);"><span class="cerb-sprite sprite-view"></span></button>
						<button type="button" onclick="genericAjaxPopup('peek','c=tickets&a=showSnippetsPeek&id=0&context=cerberusweb.contexts.worker&context_id={$active_worker->id}',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></button>
					</div>
				</fieldset>
			</div>
			
			<div id="sendMailToolbarOptions"></div>
			<div id="divDraftStatus"></div>
			
			<textarea name="content" id="content" rows="15" cols="80" class="reply required" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">{$draft->body}</textarea>
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
								<div style="margin-bottom:10px;">
									<label>
									<input type="checkbox" name="add_me_as_watcher" value="1"> 
									{'common.watchers.add_me'|devblocks_translate}
									</label>
								</div>
							
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if 'open'==$mail_status_compose}checked="checked"{/if}>{$translate->_('status.open')|capitalize}</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if 'waiting'==$mail_status_compose}checked="checked"{/if}>{$translate->_('status.waiting')|capitalize}</label>
								{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if 'closed'==$mail_status_compose}checked="checked"{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
								<br>
								<br>

								<div id="ticketClosed" style="display:{if 'open'==$mail_status_compose}none{else}block{/if};margin-left:10px;margin-bottom:10px;">
								<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
								<input type="text" name="ticket_reopen" size="55" value=""><br>
								{$translate->_('display.reply.next.resume_blank')}<br>
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
			<button type="submit" onclick="$('#btnSaveDraft').click();"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> Send Message</button>
			<button type="button" onclick="genericAjaxPost('frmCompose',null,'c=tickets&a=saveDraft&type=compose',function(o) { document.location='{devblocks_url}c=tickets{/devblocks_url}'; });"><span class="cerb-sprite sprite-media_pause"></span> {$translate->_('display.ui.continue_later')|capitalize}</button>
			<button type="button" onclick="if(confirm('Are you sure you want to discard this message?')) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id='+escape(this.form.draft_id.value), function(o) { document.location='{devblocks_url}c=tickets{/devblocks_url}'; } ); } "><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('display.ui.discard')|capitalize}</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script type="text/javascript">
	$(function() {
		ajax.emailAutoComplete('#frmCompose input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#frmCompose input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#frmCompose input[name=bcc]', { multiple: true } );
		
		$('#frmCompose').validate();
		
		setInterval("$('#btnSaveDraft').click();", 30000);

		$('#frmCompose input:text.context-snippet').autocomplete({
			source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=cerberusweb.contexts.snippet&contexts=&contexts=cerberusweb.contexts.worker',
			minLength: 1,
			focus:function(event, ui) {
				return false;
			},
			autoFocus:true,
			select:function(event, ui) {
				$this = $(this);
				$textarea = $('#frmCompose textarea#content');
				
				$label = ui.item.label.replace("<","&lt;").replace(">","&gt;");
				$value = ui.item.value;
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				url = 'c=internal&a=snippetPaste&id=' + $value;

				// Context-dependent arguments
				if ('cerberusweb.contexts.worker'==ui.item.context) {
					url += "&context_id={$active_worker->id}";
				}

				genericAjaxGet('',url,function(txt) {
					$textarea.insertAtCursor(txt);
				}, { async: false });

				$this.val('');
				return false;
			}
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
					case 9:  // (I) Insert Snippet
						try {
							$(this).closest('td').find('.context-snippet').focus();
						} catch(ex) { } 
						break;
				}
			}
		});
		
		{/if}
		
	});
	
	function openSnippetsChooser(button) {
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.snippet&contexts[]=cerberusweb.contexts.worker',null,true,'750');
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
