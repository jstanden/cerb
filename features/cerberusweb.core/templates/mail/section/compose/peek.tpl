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
		      		{foreach from=$group_buckets item=buckets key=groupId}
						{if !empty($active_worker_memberships.$groupId)}
			      			{assign var=group value=$groups.$groupId}
			      			<option value="{$group->id}_0" {if $defaults.group_id==$group->id && empty($defaults.bucket_id)}selected="selected"{/if}>{$group->name}</option>
			      			{foreach from=$buckets item=bucket}
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
				(optional) Link this ticket to an organization for suggested recipients
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'message.header.to'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="100%">
				<input type="text" name="to" id="emailinput" value="{if !empty($to)}{$to}{else}{$draft->params.to}{/if}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
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
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.cc'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.cc}" autocomplete="off">
				<div class="instructions" style="display:none;">
					These recipients will publicly receive a copy of this message	
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.bcc'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="bcc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.bcc}" autocomplete="off">
				<div class="instructions" style="display:none;">
					These recipients will secretly receive a copy of this message			
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="100%">
				<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->subject}" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="100%" colspan="2">
				<div style="padding:2px;">
					<button id="btnSaveDraft" class="toolbar-item" type="button" onclick="genericAjaxPost('frmComposePeek',null,'c=mail&a=handleSectionAction&section=drafts&action=saveDraft&type=compose',function(json) { var obj = $.parseJSON(json); if(!obj || !obj.html || !obj.draft_id) return; $('#divDraftStatus').html(obj.html); $('#frmComposePeek input[name=draft_id]').val(obj.draft_id); } );"><span class="cerb-sprite2 sprite-tick-circle"></span> Save Draft</button>
					<button class="toolbar-item" type="button" onclick="ajax.chooserSnippet('snippets',$('#divComposeContent'), { '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });"><span class="cerb-sprite sprite-view"></span> {'common.snippets'|devblocks_translate|capitalize}</button>
					<button class="toolbar-item" type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+$(this.form.group_id).val()+'&bucket_id='+$(this.form.bucket_id).val(),function(text) { insertAtCursor(document.getElementById('divComposeContent'), text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
				</div>
			
				<div id="divDraftStatus"></div>
				
				<textarea id="divComposeContent" name="content" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;">{$draft->body}</textarea>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div>
		<label>
		<input type="checkbox" name="add_me_as_watcher" value="1" {if $draft->params.add_me_as_watcher}checked="checked"{/if}> 
		{'common.watchers.add_me'|devblocks_translate}
		</label>
	</div>
	<div>
		<label>
		<input type="checkbox" name="options_dont_send" value="1" {if $draft->params.options_dont_send}checked="checked"{/if}> 
		Start a new conversation without sending a copy of this message to the recipients
		</label>
	</div>
	
	<div style="margin-top:10px;">
		<label><input type="radio" name="closed" value="0" {if (empty($drafts) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.closed==0)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','none');">{'status.open'|devblocks_translate}</label>
		<label><input type="radio" name="closed" value="2" {if (empty($drafts) && 'waiting'==$defaults.status) || (!empty($draft) && $draft->params.closed==2)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.waiting'|devblocks_translate}</label>
		{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" {if (empty($drafts) && 'closed'==$defaults.status) || (!empty($draft) && $draft->params.closed==1)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.closed'|devblocks_translate}</label>{/if}
		
		<div id="divComposeClosed" style="display:{if (empty($drafts) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.closed==0)}none{else}block{/if};margin-top:5px;margin-left:10px;">
			<b>{$translate->_('display.reply.next.resume')}</b><br>
			{$translate->_('display.reply.next.resume_eg')}<br> 
			<input type="text" name="ticket_reopen" size="55" value="{$draft->params.ticket_reopen}"><br>
			{$translate->_('display.reply.next.resume_blank')}<br>
		</div>
	</div>
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

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmComposePeek','{$view_id}',false,'compose_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
</form>

<script type="text/javascript">
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
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		$frm.validate();
		
		$frm.find('input:text').focus(function(event) {
			$(this).nextAll('div.instructions').fadeIn();
		});
		
		$frm.find('input:text').blur(function(event) {
			$(this).nextAll('div.instructions').fadeOut();
		});
		
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
		
		$frm.find(':input:text:first').focus().select();
		
		//setInterval("$('#btnSaveDraft').click();", 30000);
	});
</script>