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
<h2>{'mail.send_mail'|devblocks_translate|capitalize}</h2>
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
					<td width="0%" nowrap="nowrap" valign="top" align="right"><b>From:</b>&nbsp;</td>
					<td width="100%">
						<input type="hidden" name="group_id" value="{$defaults.group_id}">
						<input type="hidden" name="bucket_id" value="{$defaults.bucket_id}">
						<select name="group_or_bucket_id" id="group_or_bucket_id" class="required" style="border:1px solid rgb(180,180,180);padding:2px;">
				      		{foreach from=$groups item=group key=groupId}
								{if !empty($active_worker_memberships.$groupId)}
				      			<option value="{$group->id}_0" {if $defaults.group_id==$group->id && empty($defaults.bucket_id)}selected="selected"{/if}>{$group->name}</option>
					      		{foreach from=$group_buckets.$groupId item=bucket}
				    				<option value="{$group->id}_{$bucket->id}" {if $defaults.group_id==$group->id && $defaults.bucket_id==$bucket->id}selected="selected"{/if}>{$group->name}: {$bucket->name}</option>
					     		{/foreach}
								{/if}
				     		{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'contact_org.name'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="org_name" value="{$draft->params.org_name}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
						<div class="instructions" style="display:none;">
						(optional) Link this ticket to an organization and automatically suggest recipients
						</div>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top" align="right"><b>To:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="to" value="{if !empty($draft)}{$draft->params.to}{else}{$defaults_to}{/if}" class="required" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
						
						<div class="instructions" style="display:none;">
							These recipients will automatically be included in all future correspondence
						</div>
						
						<div id="compose_suggested" style="display:none;">
							<a href="javascript:;" onclick="$(this).closest('div').hide();">x</a>
							<b>Consider adding these recipients:</b>
							<ul class="bubbles"></ul> 
						</div>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top" align="right">Cc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="cc" value="{$draft->params.cc}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
						
						<div class="instructions" style="display:none;">
							These recipients will publicly receive a copy of this message	
						</div>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top" align="right">Bcc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="bcc" value="{$draft->params.bcc}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
						
						<div class="instructions" style="display:none;">
							These recipients will privately receive a copy of this message	
						</div>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top" align="right"><b>Subject:</b>&nbsp;</td>
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
					<button type="button" id="btnInsertSig" title="(Ctrl+Shift+G)" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+$(this.form.group_id).val()+'&bucket_id='+$(this.form.bucket_id).val(),function(text) { insertAtCursor(document.getElementById('content'),text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
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
						<button type="button" onclick="ajax.chooserSnippet('snippets',$('#content'), { '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });"><span class="cerb-sprite sprite-view"></span></button>
						<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showSnippetsPeek&id=0&owner_context=cerberusweb.contexts.worker&owner_context_id={$active_worker->id}',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></button>
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
									<br>
									
									<label>
									<input type="checkbox" name="options_dont_send" value="1"> 
									Start a new conversation without sending a copy of this message to the recipients
									</label>
									<br>
								</div>
								
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if 'open'==$defaults.status}checked="checked"{/if}>{$translate->_('status.open')|capitalize}</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if 'waiting'==$defaults.status}checked="checked"{/if}>{$translate->_('status.waiting')|capitalize}</label>
								{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if 'closed'==$defaults.status}checked="checked"{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
								<br>
								<br>

								<div id="ticketClosed" style="display:{if 'open'==$defaults.status}none{else}block{/if};margin-left:10px;margin-bottom:10px;">
								<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
								<input type="text" name="ticket_reopen" size="55" value=""><br>
								{$translate->_('display.reply.next.resume_blank')}<br>
								</div>
							</td>
						</tr>
					</table>

					<div style="{if empty($custom_fields) && empty($group_fields)}display:none;{/if}" id="compose_cfields">
						<b>{'common.custom_fields'|devblocks_translate|capitalize}:</b>
						<div style="margin:5px 0px 0px 10px;">
							{if !empty($custom_fields)}
							<div class="global">
								{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
							</div>
							{/if}
							<div class="group">
								{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" custom_fields=$group_fields bulk=false}
							</div>
						</div>
					</div>
					
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
		
		$frm = $('#frmCompose');
		
		$frm.validate();
		
		$frm.find('input:text').focus(function(event) {
			$(this).nextAll('div.instructions').fadeIn();
		});
		
		$frm.find('input:text').blur(function(event) {
			$(this).nextAll('div.instructions').fadeOut();
		});

		ajax.orgAutoComplete('#frmCompose input:text[name=org_name]');
		
		$frm.find('select[name=group_or_bucket_id]').change(function(e) {
			$div = $('#compose_cfields');
			$div.find('div.group').html('');
			
			$frm = $(this).closest('form');
			
			// Regexp the group_bucket pattern
			sep = /(\d+)_(\d+)/;
			hits = sep.exec($(this).val());
			
			if(hits < 3)
				return;
			
			group_id = hits[1];
			bucket_id = hits[2];
			
			$frm.find('input:hidden[name=group_id]').val(group_id);
			$frm.find('input:hidden[name=bucket_id]').val(bucket_id);
			
			genericAjaxGet($div, 'c=tickets&a=getCustomFieldEntry&group_id=' + group_id, function(html) {
				$cfields = $('#compose_cfields');
				if(html.length > 0) {
					$cfields.show().find('div.group').html(html);
				} else {
					if(0 == $cfields.find('div.global').length)
						$cfields.hide();
				}
			});
		});
		
		$frm.find('select[name=group_or_bucket_id]').trigger('change');
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#compose_suggested').appendTo($(this).closest('td'));
		});
		
		$frm.find('input:text[name=org_name]').bind('autocompletechange',function(event, ui) {
			genericAjaxGet('', 'c=contacts&a=getTopContactsByOrgJson&org_name=' + $(this).val(), function(json) {
				$sug = $('#compose_suggested');
				
				$sug.find('ul.bubbles li').remove();
				
				if(0 == json.length) {
					$sug.hide();
					return;
				}
				
				for(i in json) {
					label = '';
					if(null != json[i].name && json[i].name.length > 0) {
						label += json[i].name + " ";
						label += "&lt;" + json[i].email + '&gt;';
					} else {
						label += json[i].email;
					}
					
					$sug.find('ul.bubbles').append($("<li><a href=\"javascript:;\" class=\"suggested\">" + label + "</a></li>"));
				}
				
				// Insert suggested on click
				$sug.find('a.suggested').click(function(e) {
					$this = $(this);
					$sug = $this.text();
					
					$to=$this.closest('td').find('input:text:first');
					$val=$to.val();
					$len=$val.length;
					
					$last = null;
					if($len>0)
						$last=$val.substring($len-1);
					
					if(0==$len || $last==' ')
						$to.val($val+$sug);
					else if($last==',')
						$to.val($val + ' '+$sug);
					else $to.val($val + ', '+$sug);
						$to.focus();
					
					$ul=$this.closest('ul');
					$this.closest('li').remove();
					if(0==$ul.find('li').length)
						$ul.closest('div').remove();
				});
				
				$sug.show();
			});
		});		
		
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
			
			if(event.ctrlKey && event.shiftKey) {
				switch(event.which) {
					case 7:
					case 71: // (G) Insert Signature
						try {
							event.preventDefault();
							$('#btnInsertSig').click();
						} catch(ex) { } 
						break;
					case 9:
					case 73: // (I) Insert Snippet
						try {
							event.preventDefault();
							$(this).closest('td').find('.context-snippet').focus();
						} catch(ex) { } 
						break;
				}
			}
		});
		
		{/if}
		
	});
</script>
