<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="opportunity">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$opp_ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($opp_ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($opp_ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($opp_ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label>
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="status">
				<option value=""></option>
				<option value="open">{'crm.opp.status.open'|devblocks_translate}</option>
				<option value="won">{'crm.opp.status.closed.won'|devblocks_translate}</option>
				<option value="lost">{'crm.opp.status.closed.lost'|devblocks_translate}</option>
				{if $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.delete')}
				<option value="deleted">{'status.deleted'|devblocks_translate|capitalize}</option>
				{/if}
			</select>
			<br>
			<button type="button" onclick="this.form.status.selectedIndex = 1;">{'crm.opp.status.open'|devblocks_translate|lower}</button>
			<button type="button" onclick="this.form.status.selectedIndex = 2;">{'crm.opp.status.closed.won'|devblocks_translate|lower}</button>
			<button type="button" onclick="this.form.status.selectedIndex = 3;">{'crm.opp.status.closed.lost'|devblocks_translate|lower}</button>
			{if $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.delete')}
			<button type="button" onclick="this.form.status.selectedIndex = 4;">{'status.deleted'|devblocks_translate|lower}</button>
			{/if}
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Add watchers:</td>
			<td width="100%">
				<div>
					<button type="button" class="chooser-abstract" data-field-name="do_watcher_add_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Remove watchers:</td>
			<td width="100%">
				<div>
					<button type="button" class="chooser-abstract" data-field-name="do_watcher_remove_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>

		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="closed_date" size=35 value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppBulkClosed');"><span class="glyphicons glyphicons-calendar"></span></button>
				<div id="dateOppBulkClosed"></div>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.broadcast')}
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY}
{/if}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
	
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					$('<span class="cerb-ajax-spinner"/>').appendTo($tips);

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
		
		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>