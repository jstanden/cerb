<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="feeds">
<input type="hidden" name="a" value="doFeedItemBulkUpdate">
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
			<td width="0%" nowrap="nowrap" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="is_closed">
					<option value=""></option>
					<option value="0">{'status.open'|devblocks_translate}</option>
					<option value="1">{'status.closed'|devblocks_translate}</option>
				</select>
				<button type="button" onclick="this.form.is_closed.selectedIndex = 1;">{'status.open'|devblocks_translate|lower}</button>
				<button type="button" onclick="this.form.is_closed.selectedIndex = 2;">{'status.closed'|devblocks_translate|lower}</button>
			</td>
		</tr>
		
		{if $active_worker->hasPriv('core.watchers.assign') || $active_worker->hasPriv('core.watchers.unassign')}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top">{'common.watchers'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				{if $active_worker->hasPriv('core.watchers.assign')}
				<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-view"></span></button>
				<br>
				{/if}
				
				{if $active_worker->hasPriv('core.watchers.unassign')}
				<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-view"></span></button>
				{/if}
			</td>
		</tr>
		{/if}
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
		
		$('#formBatchUpdate button.chooser-worker').each(function() {
			$button = $(this);
			context = 'cerberusweb.contexts.worker';
			
			if($button.hasClass('remove'))
				ajax.chooser(this, context, 'do_watcher_remove_ids', { autocomplete: true, autocomplete_class:'input_remove' } );
			else
				ajax.chooser(this, context, 'do_watcher_add_ids', { autocomplete: true, autocomplete_class:'input_add'} );
		});
	});
</script>
