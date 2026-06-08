{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="home">

<fieldset class="peek">
	<legend>{'portal.sc.public.home'|devblocks_translate|capitalize}</legend>

	<b>{'portal.cfg.home_markdown'|devblocks_translate}</b>
	<div>
		<textarea name="home_markdown" class="cerb-editor" data-editor-mode="ace/mode/markdown" style="height:25em;width:90%;">{$home_markdown}</textarea>
	</div>
</fieldset>

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	$frm.find('textarea.cerb-editor')
		.cerbCodeEditor()
	;

	$frm.find('button.submit').on('click', function(e) {
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
