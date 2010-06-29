<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="doAttachmentsBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>
<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>
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

{*include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true*}	

<br>

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('dialogopen', function(event,ui) {
		$popup.dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
	} );
</script>
