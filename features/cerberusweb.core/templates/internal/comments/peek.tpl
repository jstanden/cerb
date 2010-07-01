<form action="{devblocks_url}{/devblocks_url}" method="post" id="internalCommentPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="commentSavePopup">
<input type="hidden" name="context" value="{$context|escape}">
<input type="hidden" name="context_id" value="{$context_id}">

<b>Author:</b> {$active_worker->getName()}<br>
<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea><br>
<br>

<b>Notify workers</b>:<br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-add"></span></button>
</div>

<button type="button" class="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>

<script language="JavaScript" type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	var $frm = $('#internalCommentPopup');
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','Comment');
		
		$frm.find('button.submit').click(function() {
			$popup = genericAjaxPopupFetch('peek');
			genericAjaxPost('internalCommentPopup','','', null, { async: false } );
			$popup.trigger('comment_save');
			genericAjaxPopupClose('peek');
		});
	
		$frm.find('button.chooser_worker').click(function() {
			$button = $(this);
			$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.worker',null,true,'750');
			$chooser.one('chooser_save', function(event) {
				$label = $button.prev('div.chooser-container');
				if(0==$label.length)
					$label = $('<div class="chooser-container"></div>').insertBefore($button);
				for(var idx in event.labels)
					if(0==$label.find('input:hidden[value='+event.values[idx]+']').length)
						$label.append($('<div><button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> '+event.labels[idx]+'<input type="hidden" name="notify_worker_ids[]" value="'+event.values[idx]+'"></div>'));
			});
		});
		
		// [TODO] This shouldn't catch an 'o'.
		$frm.find('textarea').focus();
	});
</script>
