<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="doTaskBulkUpdate">
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
			<td width="0%" nowrap="nowrap" align="right">{'task.due_date'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="due" size="35" value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.due,'#dateBulkTaskDue');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
				<div id="dateBulkTaskDue"></div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="status">
					<option value=""></option>
					<option value="0">{'task.status.active'|devblocks_translate}</option>
					<option value="1">{'task.status.completed'|devblocks_translate}</option>
				</select>
				<button type="button" onclick="this.form.status.selectedIndex = 1;">{'task.status.active'|devblocks_translate|lower}</button>
				<button type="button" onclick="this.form.status.selectedIndex = 2;">{'task.status.completed'|devblocks_translate|lower}</button>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top">{'common.owners'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-add"></span></button>
				<br>
				<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-forbidden"></span></button>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>Set Custom Fields</legend>
	{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

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
