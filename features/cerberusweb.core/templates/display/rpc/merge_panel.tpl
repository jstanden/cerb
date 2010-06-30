<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmDisplayMerge" name="frmDisplayMerge">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveMergePanel">
<input type="hidden" name="src_ticket_id" value="{$ticket_id}">

<b>Merge with tickets:</b><br>
<button type="button" class="chooser_ticket"><span class="cerb-sprite sprite-add"></span></button>
<br>
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{$translate->_('mail.merge')|escape}');
		
		//ajax.emailAutoComplete('#frmDisplayMerge textarea[name=req_adds]', { multiple: true } );
	});
	$('#frmDisplayMerge button.chooser_ticket').click(function() {
		$button = $(this);
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.ticket',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			$label = $button.prev('div.chooser-container');
			if(0==$label.length)
				$label = $('<div class="chooser-container"></div>').insertBefore($button);
			for(var idx in event.labels)
				if(0==$label.find('input:hidden[value='+event.values[idx]+']').length)
					$label.append($('<div><button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> '+event.labels[idx]+'<input type="hidden" name="dst_ticket_id[]" value="'+event.values[idx]+'"></div>'));
		});
	});
</script>
