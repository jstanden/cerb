<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="address">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label>
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.organization'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container"></ul>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'address.is_banned'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="is_banned">
				<option value=""></option>
				<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
				<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
			</select></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'address.is_defunct'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="is_defunct">
				<option value=""></option>
				<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
				<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
			</select></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'address.is_trusted'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="is_trusted">
				<option value=""></option>
				<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
				<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
			</select></td>
		</tr>
		{if $active_worker->is_superuser}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.email_transport'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="mail_transport_id" data-context="{CerberusContexts::CONTEXT_MAIL_TRANSPORT}" data-single="true" data-query="" data-query-required="" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container"></ul>
			</td>
		</tr>
		{/if}
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ADDRESS bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.address.broadcast')}
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_ADDRESS}
{/if}

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.address.update')}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
{/if}
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formBatchUpdate');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					Devblocks.getSpinner().appendTo($tips);

					var formData = new FormData();
					formData.set('c', 'internal');
					formData.set('a', 'invoke');
					formData.set('module', 'worklists');
					formData.set('action', 'viewBulkUpdateWithCursor');
					formData.set('view_id', '{$view_id}');
					formData.set('cursor', json.cursor);

					genericAjaxPost(formData, $tips, null);
				}
				
				genericAjaxPopupClose($popup);
			});
		});
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>