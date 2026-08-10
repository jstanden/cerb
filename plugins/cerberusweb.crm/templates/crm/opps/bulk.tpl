<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
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
			<td width="100%">
				<select name="status">
					<option value=""></option>
					<option value="open">{'crm.opp.status.open'|devblocks_translate}</option>
					<option value="won">{'crm.opp.status.closed.won'|devblocks_translate}</option>
					<option value="lost">{'crm.opp.status.closed.lost'|devblocks_translate}</option>
					{if $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.delete')}
					<option value="deleted">{'status.deleted'|devblocks_translate|capitalize}</option>
					{/if}
				</select>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Add watchers:</td>
			<td width="100%">
				<div>
					<div class="cerb-ui-record-chooser" data-cerb-chooser="do_watcher_add_ids"></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Remove watchers:</td>
			<td width="100%">
				<div>
					<div class="cerb-ui-record-chooser" data-cerb-chooser="do_watcher_remove_ids"></div>
				</div>
			</td>
		</tr>

		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="closed_date" size=35 value="">
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

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.broadcast')}
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY}
{/if}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl" peek_context=CerberusContexts::CONTEXT_OPPORTUNITY}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/bulk_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY}

{include file="devblocks:cerberusweb.core::internal/views/bulk_automations.tpl"}

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
	
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="do_watcher_add_ids"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'do_watcher_add_ids', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="do_watcher_remove_ids"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'do_watcher_remove_ids', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
		}
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.job_id) {
					cerbOpenQueueJobPeek(json.job_id, '{$view_id}');
					$('#{$view_id}_tips').html('').hide();
				}
				
				genericAjaxPopupClose($popup);
			});
		});

		$popup.find('input[name=closed_date]')
			.each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); })
			.next('button').on('click', function(e) {
				e.stopPropagation();
			}
		);

		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>