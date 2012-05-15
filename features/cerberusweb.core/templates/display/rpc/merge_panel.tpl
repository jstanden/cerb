<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmDisplayMerge" name="frmDisplayMerge">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveMergePanel">
<input type="hidden" name="src_ticket_id" value="{$ticket_id}">

<b>Merge with tickets:</b><br>
<button type="button" class="chooser_ticket"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
<br>
<br>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{$translate->_('mail.merge')}');
	});
	$('#frmDisplayMerge button.chooser_ticket').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.ticket','dst_ticket_id');
	});
</script>
