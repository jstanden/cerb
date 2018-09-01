{$div_id = "peek{uniqid()}"}

<div id="{$div_id}">
	<textarea style="height:200px;width:100%;">{$package_json}</textarea>
	
	<button type="button" class="close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "Export Bot Package");
		$popup.css('overflow', 'inherit');
		
		$popup.find('button.close')
			.on('click', function(e) {
				genericAjaxPopupClose($popup);
			})
			;
	});
});
</script>