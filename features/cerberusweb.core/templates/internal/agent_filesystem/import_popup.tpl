{$form_id = uniqid('afsImport')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_filesystem">
<input type="hidden" name="action" value="startImportJson">
<input type="hidden" name="filesystem_id" value="{$filesystem->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Import files into <b>{$filesystem->name}</b></div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.upload'|devblocks_translate|capitalize} (ZIP)</label>
			<div class="cerb-ui-file-upload" data-cerb-import-file></div>
			<div class="cerb-ui-form--help cerb-import-summary"></div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-import-options" style="display:none;">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field cerb-import-prefix-field" style="display:none;">
			<label class="cerb-ui-form--label">
				<input type="checkbox" name="strip_prefix" value="" checked="checked">
				Strip the top-level folder (<code class="cerb-import-prefix"></code>)
			</label>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">
				<input type="checkbox" name="prune_missing" value="1">
				Delete files that aren't in the ZIP
			</label>
			<div class="cerb-ui-form--help">Off by default, so anything created by hand or from the terminal survives.</div>
		</div>
	</div>
</div>

<button type="button" class="cerb-ui-button cerb-import-start" style="display:none;"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $popup = genericAjaxPopupFind('#{$form_id}');
	const $frm = $popup.find('FORM#{$form_id}');

	const $summary = $frm.find('.cerb-import-summary');
	const $options = $frm.find('.cerb-import-options');
	const $prefixField = $frm.find('.cerb-import-prefix-field');
	const $btnStart = $frm.find('.cerb-import-start');

	// The in-flight manifest lookup, so an eager Import click waits on it rather than racing it
	let inspecting = null;

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event) {
		event.stopPropagation();
		$(this).dialog('option', 'title', "Import agent files");
	});

	const funcReset = function() {
		inspecting = null;
		$summary.text('');
		$prefixField.hide();
		$options.hide();
		$btnStart.hide().prop('disabled', false);
	};

	// Bumps the resource TTL to a day and reads the archive's manifest
	const funcInspect = function(token) {
		return new Promise(function(resolve) {
			genericAjaxPost({
				c: 'profiles',
				a: 'invoke',
				module: 'agent_filesystem',
				action: 'importZipJson',
				import_token: token
			}, null, null, resolve, {
				// Without this a transport failure would leave the promise pending forever
				fail: function(err) {
					Devblocks.ajaxFail(err);
					resolve(null);
				}
			});
		});
	};

	const funcApplyManifest = function(json) {
		if(typeof json !== 'object' || null === json || true !== json.status) {
			Devblocks.createAlertError((json && json.error) ? json.error : "An unexpected error occurred.");
			$btnStart.hide();
			return false;
		}

		if(!json.num_files) {
			Devblocks.createAlertError("The archive contains no importable files.");
			$btnStart.hide();
			return false;
		}

		let summary = json.num_files.toLocaleString() + " file" + (1 === json.num_files ? '' : 's') + " to import";
		if(json.num_skipped)
			summary += " (" + json.num_skipped.toLocaleString() + " skipped)";

		$summary.text(summary);

		const commonPrefix = json.common_prefix || '';

		if(commonPrefix) {
			$frm.find('.cerb-import-prefix').text(commonPrefix);
			// A checked box posts the prefix itself; the server re-derives it and ignores a mismatch
			$frm.find('input[name=strip_prefix]').val(commonPrefix);
			$prefixField.show();
		}

		$options.show();
		return true;
	};

	// The upload happens on drop; inspect it in the background so the options fill in without a second step
	const funcOnFileChange = function(value) {
		funcReset();

		if(!value)
			return;

		$btnStart.show();
		inspecting = funcInspect(value.id).then(funcApplyManifest);
	};

	if(window.CerbUI && CerbUI.FileUpload) {
		new CerbUI.FileUpload($frm.find('[data-cerb-import-file]')[0], {
			name: 'import_token',
			emptyIcon: 'file',
			asResource: true,
			// Chrome on Windows reports a .zip as application/x-zip-compressed
			accept: '.zip,application/zip,application/x-zip-compressed',
			onError: function(msg) { Devblocks.createAlertError(msg); },
			onChange: funcOnFileChange
		});
	}

	// Queue the import job and hand it to the card widget's monitor
	$btnStart.on('click', async function(e) {
		e.preventDefault();

		$btnStart.prop('disabled', true);

		if(!(inspecting ? await inspecting : false))
			return;

		genericAjaxPost($frm, null, null, function(json) {
			if(typeof json !== 'object' || true !== json.status) {
				Devblocks.createAlertError((json && json.error) ? json.error : "Failed to start the import.");
				$btnStart.prop('disabled', false);
				return;
			}

			{if $widget_uid}
			$('#cardWidget{$widget_uid}').trigger('cerb-agent-filesystem-import-started', [json.job_id]);
			{/if}

			genericAjaxPopupClose($popup);
		});
	});
});
</script>
