{$type = $visit->get('import.last.type')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmImport">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worklists">
<input type="hidden" name="action" value="saveImport">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="import_token" value="{$import_token}">
<input type="hidden" name="import_pref_suffix" value="{$import_pref_suffix}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Associate Fields with Import Columns</legend>

	<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><b><abbr title="Checking fields here will match the imported fields against existing records rather than setting new values.  Any existing records that match all the checked fields will have the other field values set on them.">Match</abbr></b></td>
		<td style="padding-left:10px;"><b>Field</b></td>
		<td style="padding-left:10px;"><b>Set value from file column</b></td>
	</tr>
	{foreach from=$keys item=key key=token}
	{$saved_col = $saved_mapping[$token].column|default:''}
	<tr>
		<td valign="top" align="center">
			<input type="checkbox" name="sync_dupes[]" value="{$token}" {if $key.force_match}checked="checked" disabled="disabled"{elseif $saved_mapping[$token].sync_dupes}checked="checked"{/if}>
		</td>
		<td style="padding-left:10px;" valign="top">
			<span style="{if $key.required}font-weight:bold;{/if}">{$key.label|capitalize}</span>
			<input type="hidden" name="field[]" value="{$token}">
		</td>
		<td style="padding-left:10px;" valign="top">
			<select name="column[]" class="{if $key.required}required{/if}">
				<option value=""></option>
				{foreach from=$columns item=column key=pos name=columns}
					<option value="{$pos}" {if $saved_col !== '' && $saved_col == $pos}selected="selected"{/if}>Column {$smarty.foreach.columns.iteration}: {$column|capitalize}</option>
				{/foreach}
				{if $key.type == Model_CustomField::TYPE_CHECKBOX}
					<option value="yes" {if $saved_col === 'yes'}selected="selected"{/if}>{'common.yes'|devblocks_translate|lower}</option>
					<option value="no" {if $saved_col === 'no'}selected="selected"{/if}>{'common.no'|devblocks_translate|lower}</option>
				{elseif $key.type == Model_CustomField::TYPE_DATE}
					<option value="now" {if $saved_col === 'now'}selected="selected"{/if}>now</option>
				{elseif $key.type == Model_CustomField::TYPE_WORKER || $key.type == "ctx_{CerberusContexts::CONTEXT_WORKER}"}
					<option value="me" {if $saved_col === 'me'}selected="selected"{/if}>me</option>
				{/if}
				<option value="custom" {if $saved_col === 'custom'}selected="selected"{/if}>custom value:</option>
			</select>
			<div class="custom" style="{if $saved_col === 'custom'}display:block;{else}display:none;{/if}">
				<textarea cols="45" rows="2" style="width:100%;height:3.5em;" name="column_custom[]">{$saved_mapping[$token].column_custom|default:''|escape}</textarea>
			</div>
			<label for="columns[]" style="display:none;"></label>
		</td>
	</tr>
	{/foreach}
	</table>
</fieldset>

<div class="buttons">
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.continue'|devblocks_translate|capitalize}</button>
	<button type="button" class="preview"><span class="cerb-icons cerb-icon-gear"></span> {'common.preview'|devblocks_translate|capitalize}</button>
	<button type="button" class="cancel"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</div>

<div id="divImportPreview" style="margin:10px 0 0 0;border:1px solid var(--cerb-color-fieldset-border);padding:5px;height:200px;overflow-y:auto;display:none;"></div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#frmImport');
	let $frm = $popup.find('FORM#frmImport');

	$popup.one('popup_open',function(event) {
		event.stopPropagation();
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Columns
		$frm.find('select[name="column[]"]').on('change', function(e) {
			e.stopPropagation();
			$(this).nextAll('div.custom').css('display', ($(this).val() === 'custom') ? 'block' : 'none');
		});

		$frm.find('button.submit').click(function(e) {
			e.stopPropagation();
			Devblocks.clearAlerts();

			$('#divImportPreview').show().text('Importing... please wait');

			const $div = $(this).closest('div');
			$div.fadeOut();

			genericAjaxPost('frmImport', '', null, function(json) {
				$('#divImportPreview').hide().text('');
				$div.fadeIn();

				if('object' != typeof json)
					return;

				if(json.error) {
					Devblocks.createAlertError(json.error);
					return;
				}

				if(json.hasOwnProperty('job_id')) {
					const $trigger = $('<a/>')
						.attr('data-context', 'cerb.contexts.queue.job')
						.attr('data-context-id', String(json.job_id))
						.css('display', 'none')
						.appendTo('body');

					$trigger
						.cerbPeekTrigger({ width: '600' })
						.on('cerb-peek-closed', function(event) {
							event.stopPropagation();
							$trigger.remove();
							{if $view_id}
							genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
							{/if}
						})
						.trigger('click');
				}

				genericAjaxPopupDestroy('{$layer}');
			});
		});

		$frm.find('button.preview').click(function(e) {
			e.stopPropagation();
			Devblocks.clearAlerts();

			const $frm = $(this).closest('form');

			$('#divImportPreview').show().text('Loading...');

			let formData = new FormData($frm[0]);
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'worklists');
			formData.set('action', 'importPreview');
			formData.set('context', '{$context}');
			formData.set('import_token', '{$import_token}');

			genericAjaxPost(formData, '', '', function(json) {
				$('#divImportPreview').hide().text('');

				if('object' != typeof json)
					return;

				if(json.hasOwnProperty('error')) {
					Devblocks.createAlertError(json.error);
					return;
				}

				if(json.hasOwnProperty('preview_output')) {
					$('#divImportPreview').html(json.preview_output).fadeIn();
				}
			});
		});

		$frm.find('button.cancel').click(function(event) {
			event.stopPropagation();
			genericAjaxPopupDestroy('{$layer}');
		});
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
	
});
</script>