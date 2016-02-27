{$popup_id = "popup{uniqid()}"}
<div id="{$popup_id}">
<textarea style="height:500px;width:100%;" readonly="readonly" wrap="off">
{$raw_headers}
</textarea>

<button type="button" class="close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$popup_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	$popup.one('popup_open',function(event,ui) {
		// Title
		$popup.dialog('option','title', '{'Full Message Headers'|devblocks_translate|escape:'javascript' nofilter}');
		
		$popup.find('button.close').click(function() {
			genericAjaxPopupClose($popup);
		});
	});
});
</script>