<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doDraftsBulkUpdate">
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
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('common.status')|capitalize}:</td>
		<td width="100%">
			<select name="status">
				<option value="">&nbsp;</option>
				<option value="queue">Queue</option>
				<option value="draft">Draft</option>
				<option value="delete">{$translate->_('common.delete')|capitalize}</option>
			</select>
			
			<button type="button" onclick="this.form.status.selectedIndex=1;">send</button>
			<button type="button" onclick="this.form.status.selectedIndex=2;">draft</button>
			<button type="button" onclick="this.form.status.selectedIndex=3;">{$translate->_('common.delete')|lower}</button>
		</td>
	</tr>
</table>

{*include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true*}	

<br>

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
	} );
</script>
