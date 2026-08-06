<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="task">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
	
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="due">
					{'task.due_date'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<input type="text" name="params[due]" size="35" value="">
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="status">
					{'common.status'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[status]">
						<option value="0">{'status.open'|devblocks_translate}</option>
						<option value="1">{'status.completed'|devblocks_translate}</option>
						{if $active_worker->hasPriv('contexts.cerberusweb.contexts.task.delete')}
						<option value="2">{'status.deleted'|devblocks_translate}</option>
						{/if}
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="importance">
					{'common.importance'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-ui-slider" style="max-width:250px;">
						<input type="hidden" name="params[importance]" value="50">
					</div>
				</div>
			</td>
		</tr>
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="owner">
					{'common.owner'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-ui-record-chooser" data-cerb-chooser="params_owner"></div>
				</div>
			</td>
		</tr>
		{/if}

		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="project">
					{'common.project'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-ui-record-chooser" data-cerb-chooser="params_project"></div>
				</div>
			</td>
		</tr>

		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="watchers_add">
					Add watchers:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-ui-record-chooser" data-cerb-chooser="params_watchers_add"></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="watchers_remove">
					Remove watchers:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-ui-record-chooser" data-cerb-chooser="params_watchers_remove"></div>
				</div>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/bulk_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TASK}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl" peek_context=CerberusContexts::CONTEXT_TASK}

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#formBatchUpdate');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_owner"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[owner]', emptyIcon: 'user' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_project"]')[0], { context: 'cerb.contexts.task.project', name: 'params[project]', emptyIcon: 'collection' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_watchers_add"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[watchers_add]', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_watchers_remove"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[watchers_remove]', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
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

		// Calendar

		$popup.find('input[name="params[due]"]')
			.each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); })
			.next('button').on('click', function(e) {
				e.stopPropagation();
			}
		);

		// Checkboxes
		
		$popup.find('input:checkbox[name="actions[]"]').change(function() {
			$(this).closest('td').next('td').find('> div').toggle();
		});

		// Slider
		
		if(window.CerbUI && CerbUI.Slider) {
			$popup.find('div.cerb-ui-slider').each(function() {
				new CerbUI.Slider(this, { min: 0, max: 100, step: 1, midpoint: 50 });
			});
		}
		
	});
});
</script>