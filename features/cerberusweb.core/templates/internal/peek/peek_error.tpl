{$div_uniqid = uniqid()}

<div id="{$div_uniqid}" style="margin-bottom:10px;">
	<p>
		{$error_message}
	</p>
	
	<button type="button"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$div_uniqid}');
	
	$popup.one('popup_open', function(event,ui) {
		var $layer = $popup.attr('id').substring(5);
		
		$popup
			.dialog('option','title',"{'common.error'|devblocks_translate|capitalize|escape:'javascript' nofilter}")
			.dialog('option', 'close', false)
			;
		
		var $dialog = $popup.closest('div.ui-dialog');

		var $icon = $('<span class="glyphicons glyphicons-circle-exclamation-mark" style="margin-right:5px;"></span>');
		
		$dialog
			.find('div.ui-dialog-titlebar')
			.css('background-color', 'rgb(200,0,0)')
			.find('span.ui-dialog-title')
			.prepend($icon)
			;
		
		$dialog
			.find('button.ui-dialog-titlebar-close')
			.hide()
			;
		
		$popup.find('button').click(function() {
			genericAjaxPopupClose($layer);
		});
	});
});
</script>
