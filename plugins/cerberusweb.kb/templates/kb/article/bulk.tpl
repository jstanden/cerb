{$bulk_context = CerberusContexts::CONTEXT_KB_ARTICLE}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="kb">
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

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl" peek_context=$bulk_context}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/bulk_custom_fieldsets.tpl" context=$bulk_context}

{include file="devblocks:cerberusweb.core::internal/views/bulk_automations.tpl"}

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
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_watchers_add"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[watchers_add]', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="params_watchers_remove"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'params[watchers_remove]', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
		}

		// Categories

		$popup.find('select[data-cerb-node-id]').on('change', function(e) {
			e.stopPropagation();

			let $label = $popup.find('#kbCat' + $(this).attr('data-cerb-node-id'));

			$label.css({
				'color': '+' === this.value ? 'var(--cerb-color-success-text)' : ('-' === this.value ? 'var(--cerb-color-error-text)' : ''),
				'background-color': '' === this.value ? '' : 'var(--cerb-color-background-contrast-230)'
			});
		});

		// Checkboxes

		$popup.find('input:checkbox[name="actions[]"]').change(function() {
			$(this).closest('td').next('td').find('> div').toggle();
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
