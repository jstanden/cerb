{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="records">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">While commenting</div></div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle"><input type="checkbox" name="comment_disable_formatting" id="comment_disable_formatting_{$form_id}" value="1" {if $prefs.comment_disable_formatting}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
		<label for="comment_disable_formatting_{$form_id}">Disable formatting by default</label>
	</div>
</div>

<div>
	<div class="cerb-ui-toolbar-strip">
		<button type="button" id="btnSave_{$form_id}" class="cerb-ui-toolbar-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
