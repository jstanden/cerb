<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="workers">
<input type="hidden" name="action" value="doWorkersBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<fieldset class="peek">
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.disabled'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="is_disabled">
					<option value="">&nbsp;</option>
					<option value="0">{$translate->_('common.no')}</option>
					<option value="1">{$translate->_('common.yes')}</option>
				</select>
				
				<button type="button" onclick="this.form.is_disabled.selectedIndex=1;">{$translate->_('common.no')}</button>
				<button type="button" onclick="this.form.is_disabled.selectedIndex=2;">{$translate->_('common.yes')}</button>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'worker.auth_extension_id'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="auth_extension_id">
					<option value="">&nbsp;</option>
					{foreach from=$auth_extensions item=auth_extension key=auth_extension_id}
					<option value="{$auth_extension_id}">{$auth_extension->name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_WORKER bulk=true}

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
	} );
</script>
