<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="doBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>
<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<h2>{$translate->_('common.bulk_update.do')|capitalize}:</h2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.owners'|devblocks_translate|capitalize}:</td>
		<td width="100%">
			<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-add"></span></button>
			<br>
			<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-forbidden"></span></button>
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}

<br>

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
		
		$('#formBatchUpdate button.chooser-worker').each(function() {
			$button = $(this);
			context = 'cerberusweb.contexts.worker';
			
			if($button.hasClass('remove'))
				ajax.chooser(this, context, 'do_owner_remove_ids');
			else
				ajax.chooser(this, context, 'do_owner_add_ids');
		});
	});
</script>
