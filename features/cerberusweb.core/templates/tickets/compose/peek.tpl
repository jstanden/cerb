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
			<label><input type="radio" name="closed" value="0" {if 0==$default_closed}checked="checked"{/if}>Open</label>
			<label><input type="radio" name="closed" value="2" {if 2==$default_closed}checked="checked"{/if}>Waiting for reply</label>
			{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" {if 1==$default_closed}checked="checked"{/if}>Closed</label>{/if}
			<br>
			<br>

			{if $active_worker->hasPriv('core.ticket.actions.assign')}
				<b>Who should handle the follow-up?</b><br>
				<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="chooser-container bubbles" style="display:block;">
				{if !empty($context_watchers)}
					{foreach from=$context_watchers item=context_worker}
					<li>{$context_worker->getName()}<input type="hidden" name="worker_id[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
					{/foreach}
				{/if}
				</ul>
			{/if}
		</td>
	</tr>
</table>
<br>			

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formComposePeek', 'view{$view_id}')"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
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
