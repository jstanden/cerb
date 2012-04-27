<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="address_ids" value="{if is_array($address_ids)}{$address_ids|implode:','}{/if}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($address_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	
 	{if !empty($address_ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($address_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label>
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}

</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('contact_org.name')|capitalize}:</td>
			<td width="100%">
				<input type="text" name="contact_org" id="orginput" value="" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('address.is_banned')|capitalize}:</td>
			<td width="100%"><select name="is_banned">
				<option value=""></option>
				<option value="0">{$translate->_('common.no')|capitalize}</option>
				<option value="1">{$translate->_('common.yes')|capitalize}</option>
	      	</select></td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')}
<fieldset>
	<legend>Send Broadcast</legend>
	<label><input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkAddyBroadcast').toggle();"> {'common.enabled'|devblocks_translate|capitalize}</label>

	<blockquote id="bulkAddyBroadcast" style="display:none;margin:10px;">
		<b>From:</b> <br>
		<select name="broadcast_group_id">
			{foreach from=$groups item=group key=group_id}
			{if $active_worker_memberships.$group_id}
			<option value="{$group->id}">{$group->name}</option>
			{/if}
			{/foreach}
		</select>
		<br>
		<b>Subject:</b> <br>
		<input type="text" name="broadcast_subject" value="" style="width:100%;"><br>
		<b>Compose:</b> {*[<a href="#">syntax</a>]*}<br>
		<textarea name="broadcast_message" style="width:100%;height:200px;"></textarea>
		<br>
		<button type="button" onclick="ajax.chooserSnippet('snippets',$('#bulkAddyBroadcast textarea[name=broadcast_message]'), { '{CerberusContexts::CONTEXT_ADDRESS}':'', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPost('formBatchUpdate','bulkAddyBroadcastTest','c=contacts&a=doAddressBulkUpdateBroadcastTest');"><span class="cerb-sprite sprite-gear"></span> Test</button><!--
		--><select onchange="insertAtCursor(this.form.broadcast_message,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.broadcast_message.focus();">
			<option value="">-- insert at cursor --</option>
			{foreach from=$token_labels key=k item=v}
			<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
			{/foreach}
		</select>
		<br>
		<div id="bulkAddyBroadcastTest"></div>
		<b>{$translate->_('common.options')|capitalize}:</b> 
		<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
		<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
		<br>
		<b>{$translate->_('common.status')|capitalize}:</b> 
		<label><input type="radio" name="broadcast_next_is_closed" value="0"> {$translate->_('status.open')|capitalize}</label>
		<label><input type="radio" name="broadcast_next_is_closed" value="2" checked="checked"> {$translate->_('status.waiting')|capitalize}</label>
		<label><input type="radio" name="broadcast_next_is_closed" value="1"> {$translate->_('status.closed')|capitalize}</label>
	</blockquote>
</fieldset>
{/if}

{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="ajax.saveAddressBatchPanel('{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
{/if}
<br>
</form>

<script type="text/javascript">
	var $panel = genericAjaxPopupFetch('peek');
	$panel.one('popup_open',function(event,ui) {
		$panel.dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
		ajax.orgAutoComplete('#orginput');
	} );
</script>
