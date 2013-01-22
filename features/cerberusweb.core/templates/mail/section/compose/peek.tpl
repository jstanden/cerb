<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmComposePeek" name="frmComposePeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="draft_id" value="{$draft->id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}

<fieldset class="peek">
	<legend>{'common.message'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right"><b>From:</b>&nbsp;</td>
			<td width="100%">
				<input type="hidden" name="group_id" value="{$defaults.group_id}">
				<input type="hidden" name="bucket_id" value="{$defaults.bucket_id}">
				<select name="group_or_bucket_id" id="group_or_bucket_id" class="required" style="border:1px solid rgb(180,180,180);padding:2px;">
					{foreach from=$groups item=group key=groupId}
						{if !empty($active_worker_memberships.$groupId)}
							<option value="{$group->id}_0" {if $defaults.group_id==$group->id && empty($defaults.bucket_id)}selected="selected"{/if}>{$group->name}</option>
							{foreach from=$group_buckets.$groupId item=bucket key=bucket_id}
								<option value="{$group->id}_{$bucket->id}" {if $defaults.group_id==$group->id && $defaults.bucket_id==$bucket->id}selected="selected"{/if}>{$group->name}: {$bucket->name}</option>
							{/foreach}
						{/if}
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'contact_org.name'|devblocks_translate}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="org_name" value="{$draft->params.org_name}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" placeholder="(optional) Link this ticket to an organization for suggested recipients">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'message.header.to'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="100%">
				<input type="text" name="to" id="emailinput" value="{if !empty($to)}{$to}{else}{$draft->params.to}{/if}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" placeholder="These recipients will automatically be included in all future correspondence" required>
				
				<div id="compose_suggested" style="display:none;">
					<a href="javascript:;" onclick="$(this).closest('div').hide();">x</a>
					<b>Consider adding these recipients:</b>
					<ul class="bubbles"></ul> 
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.cc'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.cc}" placeholder="These recipients will publicly receive a copy of this message" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.bcc'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="bcc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.bcc}" placeholder="These recipients will secretly receive a copy of this message" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="100%">
				<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->subject}" autocomplete="off" required>
			</td>
		</tr>
		<tr>
			<td width="100%" colspan="2">
				<div id="divDraftStatus"></div>
				
				<div>
					<fieldset style="display:inline-block;">
						<legend>Actions</legend>
						
						<button id="btnComposeSaveDraft" class="toolbar-item" type="button"><span class="cerb-sprite2 sprite-tick-circle"></span> Save Draft</button>
						<button id="btnComposeInsertSig" class="toolbar-item" type="button" {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+G)"{/if}"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
					</fieldset>
				
					<fieldset style="display:inline-block;">
						<legend>{'common.snippets'|devblocks_translate|capitalize}</legend>
						<div>
							Insert: 
							<input type="text" size="25" class="context-snippet autocomplete" {if $pref_keyboard_shortcuts}placeholder="(Ctrl+Shift+I)"{/if}>
							<button type="button" onclick="ajax.chooserSnippet('snippets',$('#divComposeContent'), { '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });"><span class="cerb-sprite sprite-view"></span></button>
							<button type="button" onclick="genericAjaxPopup('add_snippet','c=internal&a=showSnippetsPeek&id=0&owner_context={CerberusContexts::CONTEXT_WORKER}&owner_context_id={$active_worker->id}&context=',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
						</div>
					</fieldset>
				</div>
				
				<textarea id="divComposeContent" name="content" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;">{$draft->body}</textarea>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div>
		<label>
		<input type="checkbox" name="options_dont_send" value="1" {if $draft->params.options_dont_send}checked="checked"{/if}> 
		Start a new conversation without sending a copy of this message to the recipients
		</label>
	</div>
	
	<div style="margin-top:10px;">
		<label><input type="radio" name="closed" value="0" {if (empty($draft) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.closed==0)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','none');">{'status.open'|devblocks_translate}</label>
		<label><input type="radio" name="closed" value="2" {if (empty($draft) && 'waiting'==$defaults.status) || (!empty($draft) && $draft->params.closed==2)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.waiting'|devblocks_translate}</label>
		{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" {if (empty($draft) && 'closed'==$defaults.status) || (!empty($draft) && $draft->params.closed==1)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.closed'|devblocks_translate}</label>{/if}
		
		<div id="divComposeClosed" style="display:{if (empty($draft) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.closed==0)}none{else}block{/if};margin-top:5px;margin-left:10px;">
			<b>{$translate->_('display.reply.next.resume')}</b><br>
			{$translate->_('display.reply.next.resume_eg')}<br> 
			<input type="text" name="ticket_reopen" size="55" value="{$draft->params.ticket_reopen}"><br>
			{$translate->_('display.reply.next.resume_blank')}<br>
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Assignments</legend>
	
	<table cellpadding="0" cellspacing="0" width="100%" border="0">
		<tr>
			<td width="1%" nowrap="nowrap" style="padding-right:10px;" valign="top">
				{'common.owner'|devblocks_translate|capitalize}:
			</td>
			<td width="99%">
				<select name="owner_id">
					<option value=""></option>
					{foreach from=$workers item=v key=k}
					{if !$v->is_disabled}
					<option value="{$k}">{$v->getName()}</option>
					{/if}
					{/foreach}
				</select>
				<button type="button" onclick="$(this).prev('select[name=owner_id]').val('{$active_worker->id}');">{'common.me'|devblocks_translate|lower}</button>
				<button type="button" onclick="$(this).prevAll('select[name=owner_id]').first().val('');">{'common.nobody'|devblocks_translate|lower}</button>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" style="padding-right:10px;" valign="top">
				{'common.watchers'|devblocks_translate|capitalize}:
			</td>
			<td width="99%">
				<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="chooser-container bubbles" style="display:block;"></ul>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek" style="{if empty($custom_fields) && empty($group_fields)}display:none;{/if}" id="compose_cfields">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
	
	{$custom_field_values = $draft->params.custom_fields}
	
	{if !empty($custom_fields)}
	<div class="global">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	</div>
	{/if}
	<div class="group">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" custom_fields=$group_fields bulk=false}
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
	<button type="button" class="chooser_file"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
	<ul class="bubbles chooser-container">
	{if $draft->params.file_ids}
	{foreach from=$draft->params.file_ids item=file_id}
		{$file = DAO_Attachment::get($file_id)}
		{if !empty($file)}
			<li><input type="hidden" name="file_ids[]" value="{$file_id}">{$file->display_name} ({$file->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if} 
	{/foreach}
	{/if}
	</ul>
</fieldset>

<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
</form>

<script type="text/javascript">
	if(draftComposeAutoSaveInterval == undefined)
		var draftComposeAutoSaveInterval = null;

	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{'mail.send_mail'|devblocks_translate|capitalize}');
		
		$frm = $('#frmComposePeek');

		ajax.emailAutoComplete('#frmComposePeek input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek input[name=bcc]', { multiple: true } );

		ajax.orgAutoComplete('#frmComposePeek input:text[name=org_name]');
		
		$frm.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
		});
		
		$frm.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		$frm.find('textarea').elastic();
		
		$frm.validate();
		
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
		
		// Insert Sig
		
		$('#btnComposeInsertSig').click(function(e) {
			var $this = $(this);
			var $frm = $this.closest('form');
			var $input_group = $frm.find('input:hidden[name=group_id]');
			var $input_bucket = $frm.find('input:hidden[name=bucket_id]');
			
			genericAjaxGet(
				'',
				'c=tickets&a=getComposeSignature&group_id='+$input_group.val()+'&bucket_id='+$input_bucket.val(),
				function(text) {
					$textarea = $('#divComposeContent');
					
					if(text.slice(-1) != "\n")
						text += "\n";
					
					$textarea.insertAtCursor(text).focus();
				}
			);
		});
		
		// Drafts
		
		$('#btnComposeSaveDraft').click(function(e) {
			var $this = $(this);
			
			if(!$this.is(':visible')) {
				clearTimeout(draftComposeAutoSaveInterval);
				draftComposeAutoSaveInterval = null;
				return;
			}
			
			if($this.attr('disabled'))
				return;
			
			$this.attr('disabled','disabled');
			
			genericAjaxPost(
				'frmComposePeek',
				null,
				'c=mail&a=handleSectionAction&section=drafts&action=saveDraft&type=compose',
				function(json) { 
					var obj = $.parseJSON(json);
					
					if(!obj || !obj.html || !obj.draft_id)
						return;
				
					$('#divDraftStatus').html(obj.html);
					
					$('#frmComposePeek input[name=draft_id]').val(obj.draft_id);
					
					$('#btnComposeSaveDraft').removeAttr('disabled');
				}
			);
		});
		
		if(null != draftComposeAutoSaveInterval) {
			clearTimeout(draftComposeAutoSaveInterval);
			draftComposeAutoSaveInterval = null;
		}
		
		draftComposeAutoSaveInterval = setInterval("$('#btnComposeSaveDraft').click();", 30000); // and every 30 sec
		
		// Snippet chooser shortcut
		
		$frm.find('input:text.context-snippet').autocomplete({
			source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=cerberusweb.contexts.snippet&contexts[]=cerberusweb.contexts.worker',
			minLength: 1,
			focus:function(event, ui) {
				return false;
			},
			autoFocus:true,
			select:function(event, ui) {
				$this = $(this);
				$textarea = $('#divComposeContent');
				
				$label = ui.item.label.replace("<","&lt;").replace(">","&gt;");
				$value = ui.item.value;
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				url = 'c=internal&a=snippetPaste&id=' + $value;

				// Context-dependent arguments
				if ('cerberusweb.contexts.worker'==ui.item.context) {
					url += "&context_id={$active_worker->id}";
				}

				genericAjaxGet('',url,function(txt) {
					// If the content has placeholders, use that popup instead
					if(txt.match(/\(__(.*?)__\)/)) {
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&text=' + encodeURIComponent(txt),null,false,'600');
					
						$popup_paste.bind('snippet_paste', function(event) {
							if(null == event.text)
								return;
						
							$textarea.insertAtCursor(event.text).focus();
						});
						
					} else {
						$textarea.insertAtCursor(txt).focus();
					}
					
				}, { async: false });

				$this.val('');
				return false;
			}
		});
		
		// Shortcuts
		
		{if $pref_keyboard_shortcuts}
		
		// Reply textbox
		$('#divComposeContent').keydown(function(event) {
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
							$('#btnComposeInsertSig').click();
						} catch(ex) { } 
						break;
					case 9:  
					case 73: // (I) Insert Snippet
						try {
							event.preventDefault();
							$('#frmComposePeek').find('INPUT:text.context-snippet').focus();
						} catch(ex) { } 
						break;
					case 17:
					case 81: // (Q) Reformat quotes
						try {
							event.preventDefault();
							var txt = $(this).val();
							
							var lines = txt.split("\n");
							
							var bins = [];
							var last_prefix = null;
							var wrap_to = 76;
							
							// Sort lines into bins
							for(i in lines) {
								var line = lines[i];
								var matches = line.match(/^((\> )+)/);
								var prefix = '';
								
								if(matches)
									prefix = matches[1];
								
								if(prefix != last_prefix)
									bins.push({ prefix:prefix, lines:[] });
								
								// Strip the prefix
								line = line.substring(prefix.length);
								
								idx = Math.max(bins.length-1, 0);
								bins[idx].lines.push(line);
								
								last_prefix = prefix;
							}
							
							// Rewrap quoted blocks
							for(i in bins) {
								prefix = bins[i].prefix;
								l = 0;
								bail = 75000; // prevent infinite loops
								
								if(prefix.length == 0)
									continue;
								
								while(undefined != bins[i].lines[l] && bail > 0) {
									line = bins[i].lines[l];
									boundary = wrap_to-prefix.length;
									
									if(line.length > boundary) {
										// Try to split on a space
										pos = line.lastIndexOf(' ', boundary);
										break_word = (-1 == pos);
										
										overflow = line.substring(break_word ? boundary : (pos+1));
										bins[i].lines[l] = line.substring(0, break_word ? boundary : pos);
										
										// If we don't have more lines, add a new one
										if(overflow) {
											if(undefined != bins[i].lines[l+1]) {
												if(bins[i].lines[l+1].length == 0) {
													bins[i].lines.splice(l+1,0,overflow);
												} else {
													bins[i].lines[l+1] = overflow + " " + bins[i].lines[l+1];
												}
											} else {
												bins[i].lines.push(overflow);
											}
										}
									}
									
									l++;
									bail--;
								}
							}
							
							out = "";
							
							for(i in bins) {
								for(l in bins[i].lines) {
									out += bins[i].prefix + bins[i].lines[l] + "\n";
								}
							}
							
							$(this).val($.trim(out));
							
						} catch(ex) { }
						break;
				}
			}
		});
		
		{/if}
		
		$frm.find(':input:text:first').focus().select();
		
		$frm.find('button.submit').click(function() {
			$frm = $(this).closest('form');
			$input = $frm.find('input#emailinput');
			
			if($frm.validate().form()) {
				if(null != draftComposeAutoSaveInterval) { 
					clearTimeout(draftComposeAutoSaveInterval);
					draftComposeAutoSaveInterval = null;
				}
				
				genericAjaxPopupPostCloseReloadView(null,'frmComposePeek','{$view_id}',false,'compose_save');
			}
		});
	});
</script>