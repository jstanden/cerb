<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formComposePeek" name="formComposePeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">From:</td>
		<td width="100%">
			<select name="team_id" style="border:1px solid rgb(180,180,180);padding:2px;">
				{foreach from=$active_worker_memberships item=membership key=group_id}
				<option value="{$group_id}" {if $default_group_id==$group_id}selected="selected"{/if}>{$teams.$group_id->name}</option>
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
			<textarea id="divComposeContent" name="content" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
			<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.team_id),function(text) { insertAtCursor(document.getElementById('divComposeContent'), text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<b>Next:</b> 
			<label><input type="radio" name="closed" value="0" {if 'open'==$mail_status_compose}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','none');">{'status.open'|devblocks_translate}</label>
			<label><input type="radio" name="closed" value="2" {if 'waiting'==$mail_status_compose}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.waiting'|devblocks_translate}</label>
			{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" {if 'closed'==$mail_status_compose}checked="checked"{/if} onclick="toggleDiv('divComposeClosed','block');">{'status.closed'|devblocks_translate}</label>{/if}
			<br>
			<br>
			
			<div id="divComposeClosed" style="display:{if 'open'==$mail_status_compose}none{else}block{/if};margin-left:10px;margin-bottom:10px;">
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
		$('#formComposePeek :input:text:first').focus().select();
	});
	$('#formComposePeek button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
</script>
