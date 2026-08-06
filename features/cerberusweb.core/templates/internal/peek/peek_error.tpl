{$div_uniqid = uniqid()}

<div id="{$div_uniqid}">
	<div class="cerb-ui-panel cerb-ui-panel--alert">
		<div class="cerb-ui-header">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-circle-exclamation-mark cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--title-sm">{'common.error'|devblocks_translate|capitalize}</div>
					<div class="cerb-ui-header--subtitle">{$error_message}</div>
				</div>
			</div>
			<div class="cerb-ui-header--right">
				<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$div_uniqid}');
	var $layer = $popup.attr('id').substring(5);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.error'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
	});

	$popup.find('button').on('click', function() {
		genericAjaxPopupClose($layer);
	});
});
</script>
