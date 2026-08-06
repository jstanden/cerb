{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="home">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'portal.sc.public.home'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.home_markdown'|devblocks_translate}</label>
			<textarea name="home_markdown" spellcheck="true">{$home_markdown}</textarea>
		</div>
	</div>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	// Home content — MarkdownEditor with the default formatting toolbar (no mode toggle; it's always markdown).
	// images:false — the public portal can't use worker-side /files URLs, so disable image paste + the Image button.
	// [TODO] add portal-appropriate image handling and re-enable.
	let homeEl = $frm.find('textarea[name=home_markdown]')[0];
	if(homeEl && window.CerbUI && CerbUI.MarkdownEditor)
		new CerbUI.MarkdownEditor(homeEl, { images: false, toolbar: { mode: false }, minHeight: 300, maxHeight: 600 });

	$frm.find('button.save').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});
});
</script>
