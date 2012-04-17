<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formComposePeek" name="formComposePeek" onsubmit="return false;">
<input type="hidden" name="c" value="mail">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="compose">
<input type="hidden" name="action" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">From:</td>
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
		<td width="0%" nowrap="nowrap" align="right">To: </td>
		<td width="100%">
			<input type="text" name="to" id="emailinput" value="{$to}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
		</td>
	</tr>
	{*
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Cc: </td>
		<td width="100%">
			<input type="text" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="" autocomplete="off">
		</td>
	</tr>
	*}
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Subject: </td>
		<td width="100%">
			<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$default_subject}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Body: </td>
		<td width="100%">
			<textarea id="divComposeContent" name="content" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
			<div>
				<button type="button" onclick="ajax.chooserSnippet('snippets',$('#divComposeContent'), { '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
				<button type="button" onclick="genericAjaxGet('','c=mail&=handleSectionAction&section=compose&action=getComposeSignature&group_id='+$(this.form.group_id).val()+'&bucket_id='+$(this.form.bucket_id).val(),function(text) { insertAtCursor(document.getElementById('divComposeContent'), text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<b>Next:</b> 
			<label><input type="radio" name="closed" value="0" {if 'open'==$defaults.status}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','none');">{'status.open'|devblocks_translate}</label>
			<label><input type="radio" name="closed" value="2" {if 'waiting'==$defaults.status}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.waiting'|devblocks_translate}</label>
			{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" {if 'closed'==$defaults.status}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.closed'|devblocks_translate}</label>{/if}
			<br>
			<br>
			
			<div id="divComposeClosed" style="display:{if 'open'==$defaults.status}none{else}block{/if};margin-left:10px;margin-bottom:10px;">
			<b>{$translate->_('display.reply.next.resume')}</b><br>
			{$translate->_('display.reply.next.resume_eg')}<br> 
			<input type="text" name="ticket_reopen" size="55" value=""><br>
			{$translate->_('display.reply.next.resume_blank')}<br>
			</div>

			<div>
				<label>
				<input type="checkbox" name="add_me_as_watcher" value="1"> 
				{'common.watchers.add_me'|devblocks_translate}
				</label>
			</div>
		</td>
	</tr>
</table>
<br>			

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formComposePeek', 'view{$view_id}')"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Compose');
		ajax.emailAutoComplete('#emailinput', { multiple: true } );
		
		$frm = $('#formComposePeek');
		
		$frm.find(':input:text:first').focus().select();
		
		$frm.find('select[name=group_or_bucket_id]').change(function(e) {
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
		});
	});
	$('#formComposePeek button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
</script>
