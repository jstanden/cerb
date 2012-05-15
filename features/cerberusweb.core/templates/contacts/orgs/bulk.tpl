<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doOrgBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="org_ids" value="{$org_ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($org_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
 	{if !empty($org_ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($org_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('contact_org.country')|capitalize}:</td>
			<td width="100%">
				<input type="text" name="country" value="" size="35">
			</td>
		</tr>
		
		{if $active_worker->hasPriv('core.watchers.assign') || $active_worker->hasPriv('core.watchers.unassign')}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}:</td>
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

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('core.addybook.org.actions.update')}<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if}
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.bulk_update'|devblocks_translate}");
		
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
