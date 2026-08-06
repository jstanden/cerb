<form action="#" method="post" id="frmBehaviorExport" class="cerb-ui-form">

<div class="cerb-ui-chip">
	<div class="cerb-ui-chip--head">Behavior</div>
	<div><div class="cerb-ui-chip--value">{$trigger->title}</div></div>
</div>

{* Standalone toolbar strip — the editor's own toolbar is suppressed in readOnly mode, so wire one directly *}
<div class="cerb-ui-editor-toolbar cerb-u-flex cerb-u-items-center cerb-u-gap-2">
	<ul class="cerb-ui-toolbar cerb-export-toolbar">
		<li data-value="copy" data-icon="copy" title="Copy to clipboard"></li>
	</ul>
</div>

<textarea data-editor-lines="16" data-editor-readonly spellcheck="false">{$behavior_json}</textarea>

<div>
	<button class="cerb-ui-button" type="button" data-cerb-button="close"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#frmBehaviorExport');
	Devblocks.formDisableSubmit($popup);

	$popup.one('popup_open', function() {
		$(this).dialog('option','title','Export Behavior');

		let editor = null;

		if(window.CerbUI && CerbUI.JsonEditor) {
			const elEditor = $popup.find('textarea').get(0);
			if(elEditor)
				editor = new CerbUI.JsonEditor(elEditor, { readOnly: true });
		}

		const elToolbar = $popup.find('ul.cerb-export-toolbar').get(0);

		if(elToolbar && window.CerbUI && CerbUI.Toolbar) {
			new CerbUI.Toolbar(elToolbar, {
				onSelect: function(item) {
					if(item && item.value === 'copy') {
						const json = editor ? editor.getValue() : '';
						navigator.clipboard.writeText(json).then(function() {
							Devblocks.createAlert('Copied to clipboard!');
						});
					}
				}
			});
		}

		$popup.find('[data-cerb-button=close]').click(function(e) {
			e.stopPropagation();
			$popup.dialog('close');
		});
	});
});
</script>
