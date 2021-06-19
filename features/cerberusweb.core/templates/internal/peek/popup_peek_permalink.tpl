{$popup_id = "popup{uniqid()}"}
<div id="{$popup_id}">
<form action="#" onsubmit="return false;">
	<div>
		<b>Share a <a href="{$url}">link</a> to this page:</b>
	</div>
	<input type="text" style="width:100%;" value="{$url}" readonly="readonly">
</form>

<button type="button" class="close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$popup_id}');
	var $popup = genericAjaxPopupFind($div);
	
	$popup.one('popup_open',function() {
		// Title
		$popup.dialog('option','title', '{'common.permalink'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		
		$popup.find('button.close').click(function() {
			genericAjaxPopupClose($popup);
		});
		
		$popup.find('input:text').select().focus();
	});
});
</script>