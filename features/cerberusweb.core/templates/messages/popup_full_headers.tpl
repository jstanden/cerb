{$popup_id = "popup{uniqid()}"}
<div id="{$popup_id}">
	<form onsubmit="return false;">
		<textarea style="height:500px;width:100%;" readonly="readonly" wrap="off">{$raw_headers}</textarea>
	</form>
	
	<div style="margin-top:0.5em;">
		<button type="button" class="close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $div = $('#{$popup_id}');
	var $popup = genericAjaxPopupFind($div);
	
	$popup.one('popup_open',function() {
		// Title
		$popup.dialog('option','title', '{'Full Message Headers'|devblocks_translate|escape:'javascript' nofilter}');
		
		$popup.find('button.close').click(function() {
			genericAjaxPopupClose($popup);
		});
	});
});
</script>