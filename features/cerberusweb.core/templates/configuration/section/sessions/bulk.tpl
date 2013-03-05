<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="sessions">
<input type="hidden" name="action" value="doSessionsBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label>
	
 	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
	 
</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">Status:</td>
			<td width="100%">
				<select name="deleted">
					<option value="">&nbsp;</option>
					<option value="1">{$translate->_('status.deleted')}</option>
				</select>
				
				<button type="button" onclick="this.form.deleted.selectedIndex=1;">{$translate->_('status.deleted')}</button>
			</td>
		</tr>
	</table>
</fieldset>

{*include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true*}	

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
	} );
</script>
