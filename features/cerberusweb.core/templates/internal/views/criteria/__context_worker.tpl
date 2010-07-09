<input type="hidden" name="oper" value="in">

<b>{$translate->_('common.workers')|capitalize}:</b><br>

<div style="margin:0px 0px 10px 10px;">
<button type="button" class="chooser_worker" onclick="criteriaChooserClick(this);"><span class="cerb-sprite sprite-add"></span></button>
</div>

<script language="JavaScript1.2" type="text/javascript">
	function criteriaChooserClick(button) {
		$button = $(button);
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.worker',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			$label = $button.prev('div.chooser-container');
			if(0==$label.length)
				$label = $('<div class="chooser-container"></div>').insertBefore($button);
			for(var idx in event.labels)
				if(0==$label.find('input:hidden[value='+event.values[idx]+']').length)
					$label.append($('<div><button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> '+event.labels[idx]+'<input type="hidden" name="worker_id[]" value="'+event.values[idx]+'"></div>'));
			$button.closest('form').find('button:last').click();
		});
	}
</script>

