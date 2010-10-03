<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doNotificationsBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'worker_event.is_read'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="is_read">
					<option value=""></option>
					<option value="0">{'common.no'|devblocks_translate}</option>
					<option value="1">{'common.yes'|devblocks_translate}</option>
				</select>
				<button type="button" onclick="this.form.is_read.selectedIndex = 2;">{'common.yes'|devblocks_translate|lower}</button>
				<button type="button" onclick="this.form.is_read.selectedIndex = 1;">{'common.no'|devblocks_translate|lower}</button>
			</td>
		</tr>
	</table>
</fieldset>

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
	});
</script>
