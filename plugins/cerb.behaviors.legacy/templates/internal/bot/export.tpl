{$div_id = "peek{uniqid()}"}

<div id="{$div_id}">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">JSON</div>
		</div>
		<textarea id="botExportJson_{$div_id}" data-editor-lines="20" spellcheck="false">{$package_json}</textarea>
	</div>

	<div class="buttons" style="margin-top:10px;">
		<button type="button" class="cerb-ui-button close"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "Export Bot Package");
		$popup.css('overflow', 'inherit');

		new CerbUI.JsonEditor($div.find('#botExportJson_{$div_id}')[0], { readOnly: true });

		$popup.find('button.close')
			.on('click', function(e) {
				genericAjaxPopupClose($popup);
			})
			;
	});
});
</script>