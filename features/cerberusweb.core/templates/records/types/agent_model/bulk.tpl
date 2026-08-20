{$bulk_context = 'cerb.contexts.agent.model'}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_model">
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
					<input type="checkbox" name="actions[]" value="status">
					{'common.status'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[status]">
						{foreach from=$statuses key=status_id item=status_label}
							<option value="{$status_id}">{$status_label|capitalize}</option>
						{/foreach}
					</select>
					<div class="cerb-ui-form--help">Routers offer <b>Available</b> models only. <b>Unlisted</b> still runs when an automation names it; <b>Disabled</b> is refused everywhere.</div>
				</div>
			</td>
		</tr>

		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="has_vision">
					{'dao.agent_model.has_vision'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[has_vision]">
						<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
						<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
					</select>
				</div>
			</td>
		</tr>

		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="has_thinking">
					{'dao.agent_model.has_thinking'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[has_thinking]">
						<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
						<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
					</select>
				</div>
			</td>
		</tr>

		{foreach from=$rating_scales key=rating item=scale}
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="rating_{$rating}">
					{"dao.agent_model.rating_`$rating`"|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[rating_{$rating}]">
						<option value="0">({'common.none'|devblocks_translate|lower})</option>
						{foreach from=$scale key=tier item=tier_label}
							<option value="{$tier}">{$tier_label|capitalize}</option>
						{/foreach}
					</select>
				</div>
			</td>
		</tr>
		{/foreach}

		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="connected_account_id">
					{'dao.agent_model.connected_account_id'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-ui-record-chooser" data-cerb-chooser="params_connected_account_id"></div>
					<div class="cerb-ui-form--help">Clear the chooser to remove the account (a local provider needs none).</div>
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

{if $active_worker->hasPriv("contexts.{$bulk_context}.delete")}
	<fieldset class="peek" data-cerb-section-name="delete">
		<legend><label><input type="checkbox" name="actions[]" value="delete"> {'common.delete'|devblocks_translate|capitalize}</label></legend>

		<div style="display:none;margin-left:10px;">
			The selected agent models will be permanently deleted. Automations that reference them by name will fail.
		</div>
	</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl" peek_context=$bulk_context}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/bulk_custom_fieldsets.tpl" context=$bulk_context}

{if $bulk_automations}
{include file="devblocks:cerberusweb.core::internal/views/bulk_automations.tpl"}
{/if}

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
		$popup.css('overflow', 'inherit');

		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_connected_account_id"]')[0], { context: '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}', name: 'params[connected_account_id]', emptyIcon: 'key' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_watchers_add"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[watchers_add]', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_watchers_remove"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[watchers_remove]', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
		}

		// Checkboxes
		$popup.find('input:checkbox[name="actions[]"]').change(function() {
			$(this).closest('td').next('td').find('> div').toggle();
		});

		let $delete_section = $popup.find('[data-cerb-section-name=delete]');

		$delete_section.find('legend input[type=checkbox]').change(function() {
			$delete_section.find('> div').toggle();
		});

		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.job_id) {
					cerbOpenQueueJobPeek(json.job_id, '{$view_id}');
					$('#{$view_id}_tips').html('').hide();
				}

				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>
